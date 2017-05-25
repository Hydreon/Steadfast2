<?php

namespace pocketmine\inventory\win10;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\inventory\win10\TransactionData;
use pocketmine\item\Item;
use pocketmine\Player;


class PlayerInventoryData {
		
	protected $cursor = null;
	/** @var PlayerInventory */
	protected $inventory;
	/** @var TransactionData[] */
	protected $transactionDataList = [];
	
	public function __construct(Player $player) {
		$this->inventory = $player->getInventory();
	}
	
	protected function resetData() {
		$this->cursor = null;
		$this->transactionDataList = [];
	}
	
	public function dropItemPreprocessing() {
		if ($this->cursor == null) {
			return;
		}
		$this->resetData();
	}
	
	public function selfInventoryLogic($slot, $newItem) {
		$this->basicInventoryLogic($slot, $newItem);
	}
	
	public function armorInventoryLogic($slot, $newItem) {
		$this->basicInventoryLogic($slot + $this->inventory->getSize(), $newItem);
	}
	
	public function otherInventoryLogic($slot, $newItem) {
		/** @var Player */
		$player = $this->inventory->getHolder();
		$currentInventory = $player->getCurrentWindow();
		if (!is_null($currentInventory)) {
			$this->basicInventoryLogic($slot, $newItem, $currentInventory);
		}
	}
	
	protected function basicInventoryLogic($slot, $newItem, $inventory = null) {
		if ($inventory == null) {
			$inventory = $this->inventory;
		}
		if ($newItem->getId() == Item::AIR) {
			$this->cursor = $this->getSlotItemBasedOnTransactions($inventory, $slot);
			if ($this->cursor->getId() == Item::AIR) {
				$this->resetData();
				$inventory->sendContents($this->inventory->getHolder());
				return;
			}
//			var_dump('get item from slot');
			$this->transactionDataList[] = new TransactionData($inventory, $slot, $this->cursor, $newItem);
		} else {
			$currentItem = $this->getSlotItemBasedOnTransactions($inventory, $slot);
			if ($this->cursor == null || !$newItem->equals($this->cursor)) {
				if ($newItem->equals($currentItem) && $newItem->count == $currentItem->count) {
					return;
				}
				// fix for items pick up
				$inventory->sendContents($this->inventory->getHolder());
//				var_dump('item is bad');
			} else {
//				var_dump('put item to slot');
				if ($currentItem->getId() == Item::AIR) {
//					var_dump('put item in empty slot');
//					if (empty($this->transactionDataList)) {
//						var_dump('HERE');
//						$inventory->sendContents($this->inventory->getHolder());
//						return;
//					}
					$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $this->cursor);
					$this->cursor = null;
				} else if ($currentItem->equals($this->cursor)) {
//					var_dump('add item to existings item');
					$this->cursor->count += $currentItem->count;
					$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $this->cursor);
					$this->cursor = null;
				} else {
//					var_dump('switch item');
					$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $this->cursor);
					$this->cursor = $currentItem;
				}
			}
			$this->tryExecuteTransactions();
		}
	}
	
	/** @todo testing */
	protected function getSlotItemBasedOnTransactions($inventory, $slot) {
		$transactionData = end($this->transactionDataList);
		while ($transactionData !== false) {
			$trInventory = $transactionData->getInventory();
			$trSlot = $transactionData->getSlot();
			if ($trInventory === $inventory && $trSlot == $slot) {	/** @todo testing */
				return $transactionData->getNewItem();
			}
			$transactionData = prev($this->transactionDataList);
		}
		return $inventory->getItem($slot);
	}
	
	protected function isMayExecuteTransactions() {
		$air = Item::get(Item::AIR);
		$oldItem = null;
		$newItem = null;
		foreach ($this->transactionDataList as $transactionData) {
			if ($oldItem == null && $newItem == null) {
				$oldItem = $transactionData->getOldItem();
				$newItem = $transactionData->getNewItem();
			} else {
				$trNewItem = $transactionData->getNewItem();
				if (!$trNewItem->equals($oldItem) || $trNewItem->getCount() != $oldItem->getCount()) {
					throw new \Exception('Aaaaaa!!!! Rollback!');
				}
				$oldItem = $transactionData->getOldItem();
			}
		}
		return $oldItem != null && $newItem != null && $oldItem->getId() == Item::AIR && $newItem->getId() == Item::AIR;
	}
	
	protected function tryExecuteTransactions() {
		try {
			if ($this->isMayExecuteTransactions()) {
//				var_dump('transactions is good');
				// prepare SimpleTransactionGroup
				$trGroup = new SimpleTransactionGroup($this->inventory->getHolder());
				foreach ($this->transactionDataList as $transactionData) {
					$trGroup->addTransaction(new BaseTransaction(
						$transactionData->getInventory(),
						$transactionData->getSlot(),
						$transactionData->getOldItem(),
						$transactionData->getNewItem()
					));
				}
				// trying execute
//				var_dump('starting transaction execituions');
				$isExecute = $trGroup->execute();
				if (!$isExecute) {
					var_dump('transaction execituions fail');
					$trGroup->sendInventories();
				}
				$this->resetData();
			}
		} catch (\Exception $e) {
//			var_dump('transactions rollback');
			// resend inventories
			$player = $this->inventory->getHolder();
			foreach ($this->transactionDataList as $transactionData) {
				$inventory = $transactionData->getInventory();
				if ($inventory instanceof PlayerInventory) {
					$inventory->sendArmorContents($player);
				}
				$inventory->sendContents($player);
			}
			$this->resetData();
		}
	}
		
}

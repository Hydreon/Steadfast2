<?php

namespace pocketmine\inventory\win10;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\win10\TransactionData;
use pocketmine\inventory\win10\Win10SimpleTransactionGroup;
use pocketmine\item\Item;
use pocketmine\Player;


class PlayerInventoryData {
	
	protected $cursor = null;
	/** @var PlayerInventory */
	protected $inventory;
	/** @var TransactionData[] */
	protected $transactionDataList = [];
	/** @var TransactionData[] */
	protected $tmpTransactionList = [];
	/** @var Item */
	protected $pickUpItem = null;
	
	public function __construct(Player $player) {
		$this->inventory = $player->getInventory();
	}
    
    public function check($inventory) {
        return $this->inventory === $inventory;
    }
	
	public function setPickUpItem($item) {
		$this->pickUpItem = $item;
	}
	
	protected function resetData() {
		$this->cursor = null;
		$this->transactionDataList = [];
		$this->tmpTransactionList = [];
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
		$currentItem = clone $this->getSlotItemBasedOnTransactions($inventory, $slot);
		$isDecreasingTransaction = $newItem->getId() == Item::AIR || ($newItem->equals($currentItem) && $newItem->count < $currentItem->count);
		if ($isDecreasingTransaction) {
			if ($newItem->getId() == Item::AIR) {
				$this->cursor = $currentItem;
				if ($this->cursor->getId() == Item::AIR) {
					$this->resetData();
					$inventory->sendContents($this->inventory->getHolder());
					return;
				}
//				var_dump('get item from slot');
				$this->transactionDataList[] = new TransactionData($inventory, $slot, $this->cursor, $newItem);
				if (!empty($this->tmpTransactionList)) {
					$this->addTransactionFromTmp();
					$this->tryExecuteTransactions();
				}
			} else {
//				var_dump('get part of item stack');
				$this->cursor = clone $currentItem;
				$this->cursor->count -= $newItem->count;
				$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
			}
		} else {
			if ($this->cursor == null || !$newItem->equals($this->cursor)) {
				if ($newItem->equals($currentItem) && $newItem->count == $currentItem->count) {
					return;
				}
				$countDiff = $newItem->count - $currentItem->count;
				if ($this->pickUpItem !== null && $this->pickUpItem->equals($newItem) && $countDiff <= $this->pickUpItem->count) {
					// fix for items pick up
					$inventory->sendContents($this->inventory->getHolder());
					$this->pickUpItem->count -= $countDiff;
					if ($this->pickUpItem->count == 0) {
						$this->pickUpItem = null;
					}
				} else if ($currentItem->getId() == Item::AIR || $newItem->equals($currentItem)) {
					if ($this->isTransactionTmp($newItem, $currentItem, $inventory)) {
						$this->tmpTransactionList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
						return;
					}
				}
				
//				var_dump('item is bad');
			} else {
//				var_dump('put item to slot');
				if ($currentItem->getId() == Item::AIR) {
//					var_dump('put item in empty slot');
					$diff = $newItem->count;
					if ($diff > 0) {
						if ($this->cursor->count == $diff) {
							$this->cursor = null;
							$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
						} else if ($this->cursor->count > $diff) {
							$this->cursor->count -= $diff;
							$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
						}
					}
				} else if ($currentItem->equals($this->cursor)) {
//					var_dump('add item to existings item');
					if ($currentItem->equals($newItem)) {
						$diff = $newItem->count - $currentItem->count;
						if ($diff > 0) {
							if ($this->cursor->count == $diff) {
								$this->cursor = null;
								$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
							} else if ($this->cursor->count > $diff) {
								$this->cursor->count -= $diff;
								$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $newItem);
							}
						}
					} 
				} else {
//					var_dump('switch item');
					$this->transactionDataList[] = new TransactionData($inventory, $slot, $currentItem, $this->cursor);
					$this->cursor = clone $currentItem;					
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
	
	protected function addTransactionFromTmp() {
		$lastTransaction = end($this->transactionDataList);
		$oldItem = $lastTransaction->getOldItem();
		foreach ($this->tmpTransactionList as $index => $trData) {
			if ($oldItem->equals($trData->getNewItem())) {
				// move transaction from tmp to ordinary
				unset($this->tmpTransactionList[$index]);
				$this->transactionDataList[] = $trData;
				break;
			}
		}
	}


	protected function isMayExecuteTransactions() {
		$newItems = [];
		$oldItems = [];
		
		foreach ($this->transactionDataList as $transactionData) {
//			echo $transactionData . PHP_EOL;
			$newItem = $transactionData->getNewItem();
			$itemId = $newItem->getId();
			if ($itemId !== Item::AIR) {
				if (!isset($newItems[$itemId])) {
					$newItems[$itemId] = 0;
				}
				$newItems[$itemId] += $newItem->getCount();
//				var_dump('Set new item: ' . $itemId . ' Count: ' . $newItems[$itemId]);
			}
			$oldItem = $transactionData->getOldItem();
			$itemId = $oldItem->getId();
			if ($itemId !== Item::AIR) {
				if (!isset($oldItems[$itemId])) {
					$oldItems[$itemId] = 0;
				}
				$oldItems[$itemId] += $oldItem->getCount();
//				var_dump('Set old item: ' . $itemId . ' Count: ' . $oldItems[$itemId]);
			}
		}
		
		foreach ($newItems as $itemId => $itemCount) {
			if (isset($oldItems[$itemId]) && $oldItems[$itemId] == $itemCount) {
//				var_dump('Unset old item:' . $itemId);
				unset($oldItems[$itemId]);
//				var_dump('Unset new item:' . $itemId);
				unset($newItems[$itemId]);
			}
		}
		
		return empty($oldItems) && empty($newItems);
	}
	
	protected function tryExecuteTransactions() {
		try {
			if ($this->isMayExecuteTransactions()) {
//				var_dump('transactions is good');
				// prepare SimpleTransactionGroup
				$trGroup = new Win10SimpleTransactionGroup($this->inventory->getHolder());
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
//					var_dump('transaction execituions fail');
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
	
	protected function isTransactionTmp($newItem, $currentItem, $inventory) {
//		var_dump('checking for tmp transaction');
		// small bad code for transaction bad order issue
		$countDiff = $newItem->count - $currentItem->count;
		$searchItem = Item::get($newItem->getId(), $newItem->getDamage(), $countDiff);
//		var_dump('Search item: ' . $searchItem->getId() . ' ' . $newItem->getDamage() . ' ' . $searchItem->count);
		$player = $this->inventory->getHolder();
		$window = $player->getCurrentWindow();
		$targetInventory = ($inventory instanceof PlayerInventory && $window != null) ? $window : $this->inventory;
//		var_dump(get_class($targetInventory));
		$items = $targetInventory->all($searchItem);
		foreach ($items as $item) {
			if ($item->count == $searchItem->count) {
//				var_dump('add tmp transaction');
				return true;
			}
		}
		return false;
	}
		
}

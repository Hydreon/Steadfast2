<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\inventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;

/**
 * This TransactionGroup only allows doing Transaction between one / two inventories
 */
class SimpleTransactionGroup implements TransactionGroup {

	private $creationTime;
	protected $hasExecuted = false;

	/** @var Player */
	protected $source = null;

	/** @var Inventory[] */
	protected $inventories = [];

	/** @var Transaction[] */
	protected $transactions = [];

	/**
	 * @param Player $source
	 */
	public function __construct(Player $source = null) {
		$this->creationTime = microtime(true);
		$this->source = $source;
	}

	/**
	 * @return Player
	 */
	public function getSource() {
		return $this->source;
	}

	public function getCreationTime() {
		return $this->creationTime;
	}

	public function getInventories() {
		return $this->inventories;
	}

	public function getTransactions() {
		return $this->transactions;
	}

	public function addTransaction(Transaction $transaction) {
		if (isset($this->transactions[spl_object_hash($transaction)])) {
			return;
		}
		foreach ($this->transactions as $hash => $tx) {
			if ($tx->getInventory() === $transaction->getInventory() && $tx->getSlot() === $transaction->getSlot()) {
				$transaction = self::mergeTransaction($tx,  $transaction);
				if ($transaction === null) {
					return;
				}
				unset($this->transactions[$hash]);
				unset($this->inventories[spl_object_hash($tx->getInventory())]);
			}
		}
		$this->transactions[spl_object_hash($transaction)] = $transaction;
		$this->inventories[spl_object_hash($transaction->getInventory())] = $transaction->getInventory();
	}

	/**
	 * @param Item[] $needItems
	 * @param Item[] $haveItems
	 *
	 * @return bool
	 */
	protected function matchItems(array &$needItems, array &$haveItems) {
		foreach ($this->transactions as $key => $ts) {
			if ($ts->getTargetItem()->getId() !== Item::AIR) {
				$needItems[] = $ts->getTargetItem();
			}
			$sourceItem = $ts->getSourceItem();
			$sourceItemIsAir = $sourceItem->getId() === Item::AIR;
			if ($ts->getSlot() == PlayerInventory120::CREATIVE_INDEX) {
				if (!$sourceItemIsAir && Item::getCreativeItemIndex($sourceItem) === -1) {					
					return false;
				}
			} else {
				$checkSourceItem = $ts->getInventory()->getItem($ts->getSlot());
				if (is_null($checkSourceItem)) {
					$checkSourceItem = Item::get(Item::AIR, 0, 0);
					error_log("--------------------------------------");
					error_log("Get item return null for inventory " . get_class() . " and slot " . $ts->getSlot());
					ob_start();
					foreach ($this->transactions as $debugTransaction) {
						echo PHP_EOL . $debugTransaction;
					}
					error_log(ob_get_contents());
					ob_end_clean();
					error_log("--------------------------------------");
				}
				if (!$checkSourceItem->deepEquals($sourceItem) || (!$sourceItemIsAir && $sourceItem->getCount() !== $checkSourceItem->getCount())) {
					return false;
				}
			}
			if (!$sourceItemIsAir) {
				$haveItems[] = $sourceItem;
			}
		}
		
		foreach ($needItems as $i => $needItem) {
			foreach ($haveItems as $j => $haveItem) {
				if ($needItem->deepEquals($haveItem)) {
					$amount = min($needItem->getCount(), $haveItem->getCount());
					$needItem->setCount($needItem->getCount() - $amount);
					$haveItem->setCount($haveItem->getCount() - $amount);
					if ($haveItem->getCount() === 0) {
						unset($haveItems[$j]);
					}
					if ($needItem->getCount() === 0) {
						unset($needItems[$i]);
						break;
					}
				}
			}
		}

		return true;
	}

	public function canExecute() {
		$haveItems = [];
		$needItems = [];

		$matchResult = $this->matchItems($haveItems, $needItems);
		return $matchResult && empty($haveItems) && empty($needItems) && !empty($this->transactions);
	}

	public function execute() {
		if ($this->hasExecuted() or ! $this->canExecute()) {
			return false;
		}

		Server::getInstance()->getPluginManager()->callEvent($ev = new InventoryTransactionEvent($this));
		if ($ev->isCancelled()) {
			$this->sendInventories();
			throw new \Exception('Event was canceled');
		}

		foreach ($this->transactions as $transaction) {			
			$transaction->getInventory()->setItem($transaction->getSlot(), $transaction->getTargetItem());
			if ($transaction->isNeedInventoryUpdate()) {
				$this->sendInventories();
			}
		}
		
		$this->hasExecuted = true;

		return true;
	}

	public function hasExecuted() {
		return $this->hasExecuted;
	}

	public function sendInventories() {
		foreach ($this->inventories as $inventory) {
			if ($inventory instanceof PlayerInventory) {
				$inventory->sendArmorContents($this->getSource());
			}
			$inventory->sendContents($this->getSource());
		}
	}
	
	/**
	 * Merge two transaction in one.
	 * It's duck tape for 1.2 enchantment
	 * 
	 * @param BaseTransaction $sourceTr
	 * @param BaseTransaction $targetTr
	 * @return BaseTransaction | null
	 */
	protected static function mergeTransaction($sourceTr, $targetTr) {
		$oldSourceItem = $sourceTr->getSourceItem();
		$newSourceItem = $targetTr->getSourceItem();
		if ($oldSourceItem->equals($newSourceItem) || $oldSourceItem->getId() == Item::AIR || $newSourceItem->getId() == Item::AIR) {
			$oldTargetItem = $sourceTr->getTargetItem();
			$newTargetItem = $targetTr->getTargetItem();
			if ($oldTargetItem->equals($newTargetItem) || $oldTargetItem->getId() == Item::AIR || $newTargetItem->getId() == Item::AIR) {
				if ($oldSourceItem->getId() != Item::AIR) {
					$oldSourceItem->count += $newSourceItem->count;
				} else {
					$oldSourceItem = $newSourceItem;
				}
				if ($oldTargetItem->getId() != Item::AIR) {
					$oldTargetItem->count += $newTargetItem->count;
				} else {
					$oldTargetItem = $newTargetItem;
				}
				return new BaseTransaction($sourceTr->getInventory(), $sourceTr->getSlot(), $oldSourceItem, $oldTargetItem);
			}
		}
		return null;
	}

}

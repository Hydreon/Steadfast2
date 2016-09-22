<?php

/**
 * TransactionGroup contains only _two_ related transaction
 */

namespace pocketmine\inventory;

use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\inventory\Transaction;
use pocketmine\item\Item;

class TransactionPair extends SimpleTransactionGroup {

	const MAX_TRANSACTION_COUNT = 2;

	public function addTransaction(Transaction $transaction) {
		if (count($this->transactions) >= self::MAX_TRANSACTION_COUNT ||
				!$this->checkTransaction($transaction)) {

			return false;
		}
		$this->transactions[spl_object_hash($transaction)] = $transaction;
		$this->inventories[spl_object_hash($transaction->getInventory())] = $transaction->getInventory();
		return true;
	}

	public function canExecute() {
		return count($this->transactions) === self::MAX_TRANSACTION_COUNT;
	}

	// source - old
	// target - new
	protected function checkTransaction(Transaction $newTransaction) {
		$this->transactions['tmp'] = $newTransaction;

		foreach ($this->transactions as $tsKey => $ts) {
			if ($tsKey !== 'tmp' && 
					$ts->getInventory() === $newTransaction->getInventory() && 
					$ts->getSlot() === $newTransaction->getSlot()) {
				
				unset($this->transactions['tmp']);
				return false;
			}
			
			$currentSlotItem = $ts->getInventory()->getItem($ts->getSlot());
			$tsSourceItem = $ts->getSourceItem();

			if (!$currentSlotItem->deepEquals($tsSourceItem) ||
					$currentSlotItem->getCount() !== $tsSourceItem->getCount()) {

				unset($this->transactions['tmp']);
				return false;
			}
		}

		if (count($this->transactions) == 1) {
			unset($this->transactions['tmp']);
			return true;
		}

		$itemsData = [];
		$sourceItemSum = 0;
		$targetItemSum = 0;
		foreach ($this->transactions as $ts) {
			$sourceItem = $ts->getSourceItem();
			if ($sourceItem->getId() !== Item::AIR) {
				if (isset($itemsData[$sourceItem->getId()])) {
					$itemsData[$sourceItem->getId()] += $sourceItem->getCount();
				} else {
					$itemsData[$sourceItem->getId()] = $sourceItem->getCount();
				}
				$sourceItemSum += $sourceItem->getCount();
			}
			$targetItem = $ts->getTargetItem();
			if ($targetItem->getId() !== Item::AIR) {
				if (isset($itemsData[$targetItem->getId()])) {
					$itemsData[$targetItem->getId()] -= $targetItem->getCount();
				} else {
					$itemsData[$targetItem->getId()] = -1 * $targetItem->getCount();
				}
				$targetItemSum += $targetItem->getCount();
			}
		}
		unset($this->transactions['tmp']);
		
		if ($sourceItemSum !== $targetItemSum) {
			return false;
		}

		foreach ($itemsData as $itemCount) {
			if ($itemCount !== 0) {
				return false;
			}
		}
		return true;
	}

	public function sendInventories() {
//		echo '--------------- Transaction canceled 1 -------------------'.PHP_EOL;
//		foreach ($this->transactions as $ts) {
//			echo 'Old: '.$ts->getSourceItem()->getId().' Count: '.$ts->getSourceItem()->getCount().PHP_EOL;
//			echo 'New: '.$ts->getTargetItem()->getId().' Count: '.$ts->getTargetItem()->getCount().PHP_EOL;
//		}
//		echo '-----------------------------------------------------------'.PHP_EOL;
		
		foreach ($this->inventories as $inventory) {
			if ($inventory instanceof PlayerInventory) {
				$inventory->sendArmorContents($this->getSource());
			}
			$inventory->sendContents($this->getSource());
		}
	}
	
	public function execute() {
		$result = parent::execute();
//		if ($result) {
//			echo '--------------- Transaction executed -------------------'.PHP_EOL;
//		} else {
//			echo '--------------- Transaction canceled 2 -------------------'.PHP_EOL;
//		}
//		foreach ($this->transactions as $ts) {
//			echo 'Old: '.$ts->getSourceItem()->getId().' Count: '.$ts->getSourceItem()->getCount().PHP_EOL;
//			echo 'New: '.$ts->getTargetItem()->getId().' Count: '.$ts->getTargetItem()->getCount().PHP_EOL;
//		}
//		echo '-----------------------------------------------------------'.PHP_EOL;
		return $result;
	}

}

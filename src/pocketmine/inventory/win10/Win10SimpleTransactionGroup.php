<?php

namespace pocketmine\inventory\win10;

use pocketmine\inventory\SimpleTransactionGroup;
use pocketmine\item\Item;
use pocketmine\inventory\Transaction;

class Win10SimpleTransactionGroup extends SimpleTransactionGroup {

	// target - new
	// source - old
	protected function matchItems(array &$needItems, array &$haveItems) {
		foreach ($this->transactions as $ts) {
//			echo 'New: ' . $ts->getTargetItem()->getId() . ' Old: ' . $ts->getSourceItem()->getId() . PHP_EOL;
			$newItem = $ts->getTargetItem();
			if ($newItem->getId() !== Item::AIR) {
//				var_dump('Set new item: ' . $newItem->getId());
				$needItems[] = $newItem;
			}
			$oldItem = $ts->getSourceItem();
			if ($oldItem->getId() !== Item::AIR) {
//				var_dump('Set old item: ' . $oldItem->getId());
				$haveItems[] = $oldItem;
			}
		}

		foreach ($needItems as $i => $needItem) {
			foreach ($haveItems as $j => $haveItem) {
				if ($needItem->deepEquals($haveItem)) {
//					var_dump($needItem->getId() . ' == ' . $haveItem->getId());
					$amount = min($needItem->getCount(), $haveItem->getCount());
					$needItem->setCount($needItem->getCount() - $amount);
					$haveItem->setCount($haveItem->getCount() - $amount);
					if ($haveItem->getCount() === 0) {
						unset($haveItems[$j]);
//						var_dump('Unset old item:' . $haveItem->getId());
					}
					if ($needItem->getCount() === 0) {
						unset($needItems[$i]);
//						var_dump('Unset new item:' . $needItem->getId());
						break;
					}
				}
			}
		}

		return true;
	}
	
	
	public function addTransaction(Transaction $transaction) {
		if (isset($this->transactions[spl_object_hash($transaction)])) {
			return;
		}
		$this->transactions[spl_object_hash($transaction)] = $transaction;
		$this->inventories[spl_object_hash($transaction->getInventory())] = $transaction->getInventory();
	}
	
}

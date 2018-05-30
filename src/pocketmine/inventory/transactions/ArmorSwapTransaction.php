<?php

namespace pocketmine\inventory\transactions;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class ArmorSwapTransaction extends BaseTransaction {

	protected $foundTransactions = [
		'source' => false,
		'target' => false,
	];

	public function __construct(Inventory $inventory, $slot, Item $sourceItem, Item $targetItem) {
		parent::__construct($inventory, $slot, $sourceItem, $targetItem);
		$this->requiredTransactionNumber = 2;
	}
	
	public function resetFountTransaction() {
		$this->foundTransactions = [
			'source' => false,
			'target' => false,
		];
	}
	
	/**
	 * 
	 * @param BaseTransaction $ts
	 * @return bool
	 */
	public function isSuitable($ts) {
		$sourceFound = false;
		$targetFound = false;
		
		$sourceItem = $ts->getSourceItem();
		$targetItem = $ts->getTargetItem();
		
		// check for source transaction
		if ($this->sourceItem->deepEquals($targetItem)) {
			$sourceFound = true;
		}
		
		// check for target transaction
		if ($this->targetItem->deepEquals($sourceItem)) {
			$targetFound = true;
		}

		// 2 transaction case
		if ($sourceFound && $targetFound && !$this->foundTransactions['source'] && !$this->foundTransactions['target']) {
			$this->foundTransactions['source'] = true;
			$this->foundTransactions['target'] = true;
			return true;
		}
		// 3 transaction case
		if ($sourceFound && !$this->foundTransactions['source'] && $sourceItem->getId() === Item::AIR) {
			$this->foundTransactions['source'] = true;
			return true;
		}
		// also 3 transaction case
		if ($targetFound && !$this->foundTransactions['target'] && $targetItem->getId() === Item::AIR) {
			$this->foundTransactions['target'] = true;
			return true;
		}
		
		return false;
	}

	public function isFoundAll() {
		return $this->foundTransactions['source'] && $this->foundTransactions['target'];
	}
}

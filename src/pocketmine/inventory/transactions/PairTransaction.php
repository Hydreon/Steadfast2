<?php

namespace pocketmine\inventory\transactions;

use pocketmine\inventory\BaseTransaction;
use pocketmine\item\Item;

class PairTransaction extends BaseTransaction {

	protected $requiredTransactionNumber = 1;

	/**
	 * 
	 * @param BaseTransaction $ts
	 * @return bool
	 */
	public function isSuitable($ts) {
		$itemsData = [];
		$itemsData['items'] = [];
		$itemsData['currentItemsSum'] = 0;
		$itemsData['newItemsSum'] = 0;
		
		if ($ts->getInventory() === $this->inventory && $ts->getSlot() === $this->slot) {
			return false;
		}

		// source - old
		// target - new
		$this->collectItemCount($ts->getSourceItem(), $itemsData, false);
		$this->collectItemCount($ts->getTargetItem(), $itemsData, true);
		$this->collectItemCount($this->sourceItem, $itemsData, false);
		$this->collectItemCount($this->targetItem, $itemsData, true);
		
		if ($itemsData['currentItemsSum'] !== $itemsData['newItemsSum']) {
			return false;
		}

		foreach ($itemsData['items'] as $itemCount) {
			if ($itemCount !== 0) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function collectItemCount($item, &$itemsData, $isNew = false) {
		$itemId = $item->getId();
		$itemCount = $item->getCount();
		
		if ($itemId !== Item::AIR) {
			if (!isset($itemsData['items'][$itemId])) {
				$itemsData['items'][$itemId] = 0;
			}
			$itemsData['items'][$itemId] += ( $isNew ? -1 : 1 ) * $itemCount;
			if ($isNew == false) {
				$itemsData['currentItemsSum'] += $itemCount;
			} else {
				$itemsData['newItemsSum'] += $itemCount;
			}
		}
	}
	
	public function getRequiredTransactionNumber() {
		return $this->requiredTransactionNumber;
	}

}

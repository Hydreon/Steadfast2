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

use pocketmine\item\Item;

class BaseTransaction implements Transaction {

	/** @var Inventory */
	protected $inventory;

	/** @var int */
	protected $slot;

	/** @var Item */
	protected $sourceItem;

	/** @var Item */
	protected $targetItem;

	/** @var float */
	protected $creationTime;
	
	protected $needInventoryUpdate = false;

	/**
	 * @param Inventory $inventory
	 * @param int       $slot
	 * @param Item      $sourceItem
	 * @param Item      $targetItem
	 */
	public function __construct(Inventory $inventory, $slot, Item $sourceItem, Item $targetItem) {
		$this->inventory = $inventory;
		$this->slot = (int) $slot;
		$this->sourceItem = clone $sourceItem;
		$this->targetItem = clone $targetItem;
		$this->creationTime = microtime(true);
	}
	
	public function __toString() {
		return "Inventory: " . get_class($this) . " Slot: " . $this->slot . " Old item: " . $this->sourceItem . " New item: " . $this->targetItem;
	}

	public function getCreationTime() {
		return $this->creationTime;
	}

	public function getInventory() {
		return $this->inventory;
	}

	public function getSlot() {
		return $this->slot;
	}

	public function getSourceItem() {
		return clone $this->sourceItem;
	}

	public function getTargetItem() {
		return clone $this->targetItem;
	}
	
	public function setTargetItem($item) {
		$this->targetItem = $item;
		$this->needInventoryUpdate = true;
	}
	
	public function isNeedInventoryUpdate() {
		return $this->needInventoryUpdate;
	}

	/**
	 * 
	 * @param Player $target
	 */
	public function revert($target) {
		$this->inventory->sendContents($target);
	}
	
	public function clearCustomNames($target = 'both') {
		if ($target === 'source' || $target === 'both') {
			$this->sourceItem->clearCustomName();
		}
		if ($target === 'target' || $target === 'both') {
			$this->targetItem->clearCustomName();
		}
	}

}

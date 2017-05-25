<?php

namespace pocketmine\inventory\win10;

class TransactionData {
	
	protected $inventory = null;
	protected $slot = -1;
	protected $oldItem = null;
	protected $newItem = null;
	
	public function __construct($inventory, $slot, $oldItem, $newItem) {
		$this->inventory = $inventory;
		$this->slot = $slot;
		$this->oldItem = $oldItem;
		$this->newItem = $newItem;
	}
	
	public function getInventory() {
		return $this->inventory;
	}

	public function getSlot() {
		return $this->slot;
	}
	
	public function getOldItem() {
		return $this->oldItem;
	}
	
	public function getNewItem() {
		return $this->newItem;
	}
	
}

<?php

namespace pocketmine\inventory\win10;

use pocketmine\Player;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\item\Armor;

class PlayerInventoryData {
	
	protected $cursor = null;
	/** @var PlayerInventory */
	protected $inventory;
	protected $tmpItemsList = [];
	
	public function __construct(Player $player) {
		$this->inventory = $player->getInventory();
	}
	
	public function dropItemPreprocessing() {
		var_dump('drop item');
		if ($this->cursor == null) {
			return;
		}
		$this->inventory->addItem($this->cursor);
		$this->cursor = null;
	}
	
	public function selfInventoryLogic($slot, $newItem) {
		$this->basicInventoryLogic($slot, $newItem);
	}
	
	public function armorInventoryLogic($slot, $newItem) {
		if ($newItem->getId() == Item::AIR) {
			// get item from slot
			var_dump('Armor: get item from slot');
			$this->cursor = $this->inventory->getArmorItem($slot);
			$this->inventory->setArmorItem($slot, $newItem);
		} else {
			// put item to slot
			var_dump('Armor: put item to slot');
			if ($this->cursor == null || !$newItem->equals($this->cursor)) {
				// item is bad
				var_dump('Armor: item is bad');
				$this->inventory->sendArmorContents($this->inventory->getHolder());
				return;
			} else {
				$currentItem = $this->inventory->getArmorItem($slot);
				if ($currentItem->getId() == Item::AIR) {
					// put item in empty slot
					var_dump('Armor: put item in empty slot');
					$this->inventory->setArmorItem($slot, $this->cursor);
					$this->cursor = null;
				} else {
					// switch item
					var_dump('Armor: switch item');
					$this->inventory->setArmorItem($slot, $this->cursor);
					$this->cursor = $currentItem;
				}
				$this->inventory->sendArmorContents($this->inventory->getHolder());
			}
		}
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
			$this->cursor = $inventory->getItem($slot);
			if ($this->cursor->getId() == Item::AIR) {
				$this->cursor = null;
				$inventory->sendContents($this->inventory->getHolder());
				return;
			}
			// get item from slot
			$inventory->setItem($slot, $newItem);
		} else {
			// put item to slot
			if ($this->cursor == null || !$newItem->equals($this->cursor)) {
				$currentItem = $inventory->getItem($slot);
				if ($newItem->equals($currentItem) && $newItem->count == $currentItem->count) {
					return;
				}
				// item is bad
			} else {
				$currentItem = $inventory->getItem($slot);
				if ($currentItem->getId() == Item::AIR) {
					// put item in empty slot
					$inventory->setItem($slot, $this->cursor);
					$this->cursor = null;
				} else if ($currentItem->equals($this->cursor)) {
					// add item to existings item
					$currentItem->count += $this->cursor->count;
					$inventory->setItem($slot, $currentItem);
					$this->cursor = null;
				} else {
					// switch item
					$inventory->setItem($slot, $this->cursor);
					$this->cursor = $currentItem;
				}
			}
			$inventory->sendContents($this->inventory->getHolder());
		}
	}
		
}

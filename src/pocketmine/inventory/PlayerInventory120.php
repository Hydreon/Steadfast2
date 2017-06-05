<?php

namespace pocketmine\inventory;

use pocketmine\entity\Human;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventorySlotPacket;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\Player;

class PlayerInventory120 extends PlayerInventory {

	const CURSOR_INDEX = -1;
	
	/** @var Item */
	protected $cursor;
	
	public function __construct(Human $player) {
		parent::__construct($player);
		$this->cursor = Item::get(Item::AIR, 0, 0);
	}
	
	public function setItem($index, Item $item, $sendPacket = true) {
		if ($index == self::CURSOR_INDEX) {
			$this->cursor = clone $item;
			$this->sendCursor();
		} else {
			parent::setItem($index, $item, $sendPacket);
		}
	}
	
	public function getItem($index) {
		if ($index == self::CURSOR_INDEX) {
			return $this->cursor == null ? clone $this->air : clone $this->cursor;
		} else {
			return parent::getItem($index);
		}
	}
	
	public function sendSlot($index, $target) {
		$pk = new InventorySlotPacket();
		$pk->containerId = Protocol120::CONTAINER_ID_INVENTORY;
		$pk->slot = $index;
		$pk->item = $this->getItem($index);
		$this->holder->dataPacket($pk);
	}
	
	public function sendContents($target) {
		$pk = new InventoryContentPacket();
		$pk->inventoryID = Protocol120::CONTAINER_ID_INVENTORY;
		$pk->items = [];

		$mainPartSize = $this->getSize();
		for ($i = 0; $i < $mainPartSize; $i++) { //Do not send armor by error here
			$pk->items[$i] = $this->getItem($i);
		}

		$this->holder->dataPacket($pk);
		$this->sendCursor();
	}
	
	public function sendCursor() {
		if ($this->cursor == null) {
			return;
		}
		$pk = new InventorySlotPacket();
		$pk->containerId = Protocol120::CONTAINER_ID_CURSOR_SELECTED;
		$pk->slot = 0;
		$pk->item = $this->cursor;
		$this->holder->dataPacket($pk);
	}

	public function sendArmorContents($target) {
		if ($target instanceof Player) {
			$target = [$target];
		}

		$armor = $this->getArmorContents();

		$pk = new MobArmorEquipmentPacket();
		$pk->eid = $this->holder->getId();
		$pk->slots = $armor;

		foreach ($target as $player) {
			if ($player === $this->holder) {
				$pk2 = new InventoryContentPacket();
				$pk2->inventoryID = Protocol120::CONTAINER_ID_ARMOR;
				$pk2->items = $armor;
				$player->dataPacket($pk2);
			} else {
				$player->dataPacket($pk);
			}
		}
	}

}

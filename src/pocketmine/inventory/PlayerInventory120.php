<?php

namespace pocketmine\inventory;

use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventorySlotPacket;
use pocketmine\Player;

class PlayerInventory120 extends PlayerInventory {

	public function sendSlot($index, $target) {
		$pk = new InventorySlotPacket();
		$pk->containerId = InventoryContentPacket::CONTAINER_ID_INVENTORY;
		$pk->slot = $index;
		$pk->item = $this->getItem($index);
		$this->holder->dataPacket($pk);
	}
	
	public function sendContents($target) {
		$pk = new InventoryContentPacket();
		$pk->inventoryID = InventoryContentPacket::CONTAINER_ID_INVENTORY;
		$pk->items = [];

		$mainPartSize = $this->getSize();
		for ($i = 0; $i < $mainPartSize; $i++) { //Do not send armor by error here
			$pk->items[$i] = $this->getItem($i);
//			$pk->items[$i] = Item::get(Item::WOOL);
		}

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
				$pk2->inventoryID = InventoryContentPacket::CONTAINER_ID_ARMOR;
				$pk2->items = $armor;
				$player->dataPacket($pk2);
			} else {
				$player->dataPacket($pk);
			}
		}
	}

}

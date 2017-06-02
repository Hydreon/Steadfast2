<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class InventoryContentPacket extends PEPacket {
	
	const NETWORK_ID = Info120::INVENTORY_CONTENT_PACKET;
	const PACKET_NAME = "INVENTORY_CONTENT_PACKET";
	
	const CONTAINER_ID_NONE = -1;
	const CONTAINER_ID_INVENTORY = 0;
	const CONTAINER_ID_FIRST = 1;
	const CONTAINER_ID_LAST = 100;
	const CONTAINER_ID_OFFHAND = 119;
	const CONTAINER_ID_ARMOR = 120;
	const CONTAINER_ID_CREATIVE = 121;
	const CONTAINER_ID_SELECTION_SLOTS = 122;
	const CONTAINER_ID_FIXEDINVENTORY = 123;
	const CONTAINER_ID_CURSOR_SELECTED = 124;
	
	public $inventoryID;
	public $items;
	
	public function decode($playerProtocol) {
		var_dump('decode: ' . __CLASS__);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->inventoryID);
		$itemsNum = count($this->items);
		$this->putVarInt($itemsNum);
		for ($i = 0; $i < $itemsNum; $i++) {
			$this->putSlot($this->items[$i], $playerProtocol);
		}
	}
	
}

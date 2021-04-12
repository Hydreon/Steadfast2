<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class InventoryContentPacket extends PEPacket {
	
	const NETWORK_ID = Info331::INVENTORY_CONTENT_PACKET;
	const PACKET_NAME = "INVENTORY_CONTENT_PACKET";
	
	public $inventoryID;
	public $items = [];
	
	public function decode($playerProtocol) {
		var_dump('decode: ' . __CLASS__);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->inventoryID);
		$itemsNum = count($this->items);
		$this->putVarInt($itemsNum);
		$index = 1;
		for ($i = 0; $i < $itemsNum; $i++) {
			if ($playerProtocol >= Info::PROTOCOL_419 && $playerProtocol <= Info::PROTOCOL_428) {
				if ($this->items[$i]->getId() == 0) {
					$this->putSignedVarInt(0);
				} else {
					$this->putSignedVarInt($index++);
				}
			}
			$this->putSlot($this->items[$i], $playerProtocol);
		}
	}
	
}

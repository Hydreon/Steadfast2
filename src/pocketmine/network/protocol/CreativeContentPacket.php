<?php

namespace pocketmine\network\protocol;

use http\Env\Request;
use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\Info;

class CreativeContentPacket extends PEPacket {

	const NETWORK_ID = Info331::CREATIVE_ITEMS_LIST_PACKET;
	const PACKET_NAME = "CREATIVE_ITEMS_LIST_PACKET";
	

	public $groups;
	public $items;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt(count($this->items));
		$index = 1;
		foreach ($this->items as $itemData) {
			$this->putVarInt($index++);
			$this->putSlot($itemData['item'], $playerProtocol);
		}
	}

}
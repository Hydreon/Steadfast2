<?php

namespace pocketmine\network\protocol\v392;

use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info331;

class CreativeItemsListPacket extends PEPacket {

	const NETWORK_ID = Info331::CREATIVE_ITEMS_LIST_PACKET;
	const PACKET_NAME = "CREATIVE_ITEMS_LIST_PACKET";
	

	public $groups;
	public $items;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt(count($this->groups));
		foreach ($this->groups as $groupData) {
			$this->putString($groupData['name']);
			$this->putLInt($groupData['item']);
			$this->putVarInt(0); // nbt count
		}
		$this->putVarInt(count($this->items));
		$index = 1;
		foreach ($this->items as $itemData) {
			$this->putVarInt($index++);
			$this->putVarInt($itemData['group']);
			$this->putSlot($itemData['item'], $playerProtocol);
		}
	}

}

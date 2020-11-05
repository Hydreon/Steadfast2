<?php

namespace pocketmine\network\protocol;

class ItemComponentPacket extends PEPacket {

	const NETWORK_ID = Info::ITEM_COMPONENT_PACKET;
	const PACKET_NAME = "ITEM_COMPONENT_PACKET";


	public $items = [];

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
 		$this->putVarInt(0); // Send empty array for now
	}

	public function decode($playerProtocol) {

	}

}
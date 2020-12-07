<?php

namespace pocketmine\network\protocol;

use http\Env\Request;
use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\Info;

class ItemStackResponsePacket extends PEPacket {

	const NETWORK_ID = Info331::ITEM_STACK_RESPONSE_PACKET;
	const PACKET_NAME = "ITEM_STACK_RESPONSE_PACKET";
	

	public $groups;
	public $items;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
	}

}
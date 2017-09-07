<?php

namespace pocketmine\network\protocol;

class ResourcePackStackPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_STACK_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_STACK_PACKET";

	public function decode($playerProtocol) {
		
	}
	
	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte(0);
		$this->putVarInt(0);
		$this->putVarInt(0);
	}

}

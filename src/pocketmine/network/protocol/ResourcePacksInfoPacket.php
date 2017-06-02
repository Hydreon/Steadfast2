<?php

namespace pocketmine\network\protocol;

class ResourcePacksInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_INFO_PACKET";

	// read
	public function decode($playerProtocol) {
		
	}
	
	// write
	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte(0);// bool
		
		$this->putShort(0);// short - some sort of count
		
		// следующие 3 строки повторяются 
		// string
		// string
		// long
		
		$this->putShort(0);// short - some sort of count
		
		// следующие 3 строки повторяются 
		// string
		// string
		// long
	}

}

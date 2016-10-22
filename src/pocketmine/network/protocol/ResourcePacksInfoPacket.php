<?php

namespace pocketmine\network\protocol;

class ResourcePacksInfoPacket extends DataPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_INFO_PACKET;

	// read
	public function decode() {
		
	}
	
	// write
	public function encode() {
		$this->reset();
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

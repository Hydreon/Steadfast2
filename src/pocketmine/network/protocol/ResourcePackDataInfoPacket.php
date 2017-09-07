<?php

namespace pocketmine\network\protocol;

class ResourcePackDataInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACK_DATA_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_DATA_INFO_PACKET";

	// read
	public function decode($playerProtocol) {
		
	}
	
	// write
	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		
//		$json = '{"header": {"pack_id": "fe9f8597-5454-481a-8730-8d070a8e2e58", "name": "resourcePack.vanilla_server.name", "packs_version": "0.0.1", "modules": [{"description": "resourcePack.vanilla_server.description", "version": "0.0.1", "uuid": "79fccc3b-7bad-4f4f-aa97-d98108e6aa33", "type": "data"}], "dependencies": [{"description": "resourcePack.vanilla.description", "version": "0.0.1", "uuid": "53644fac-a276-42e5-843f-a3c6f169a9ab", "type": "resources"}]}}';
		
		$this->putString('53644fac-a276-42e5-843f-a3c6f169a9ab');
		$this->putInt(1);
		$this->putInt(0);
		$this->putLong(1);
		$this->putString('resources');
		
//		$this->putString('resourcePack.vanilla.name');
//		$this->putString('test');
//		$this->putVarInt(1);
//		$this->putVarInt(1);
//		$this->putVarInt(1);
//		
//		for ($i = 1; $i < 100; $i++) {
//			$this->buffer .= chr(1);
//			$this->buffer .= chr(0);
//		}
	}

}

<?php

namespace pocketmine\network\protocol;

class ResourcePackDataInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACK_DATA_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_DATA_INFO_PACKET";

	const MAX_CHUNK_SIZE = 1048576; // 1MB
	
	public $modId = "";
	public $fileSize = 0;
	public $modFileHash = "";


	// read
	public function decode($playerProtocol) {
		
	}
	
	// write
	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->modId);
		$this->putLInt(self::MAX_CHUNK_SIZE);
		$this->putLInt(ceil($this->fileSize / self::MAX_CHUNK_SIZE)); // chunks count
		$this->putLLong($this->fileSize);
		$this->putString($this->modFileHash);
	}

}

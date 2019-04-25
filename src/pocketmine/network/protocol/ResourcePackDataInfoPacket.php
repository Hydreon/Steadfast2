<?php

namespace pocketmine\network\protocol;

class ResourcePackDataInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACK_DATA_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_DATA_INFO_PACKET";

	const MAX_CHUNK_SIZE = 1048576; // 1MB

	const TYPE_INVALID = 0;
	const TYPE_RESOURCE = 1;
	const TYPE_BEHAVIOR = 2;
	const TYPE_WORLD_TEMPLATE = 3;
	const TYPE_ADDON = 4;
	const TYPE_SKINS = 5;
	const TYPE_CACHED = 6;
	const TYPE_COPY_PROTECTED = 7;
	const TYPE_COUNT = 8;
	
	public $modId = "";
	public $fileSize = 0;
	public $modFileHash = "";
	public $isPremium = false;
	public $type = self::TYPE_RESOURCE;

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
		if ($playerProtocol >= Info::PROTOCOL_360) {
			$this->putByte($this->isPremium);
			$this->putByte($this->type);
		}
	}

}

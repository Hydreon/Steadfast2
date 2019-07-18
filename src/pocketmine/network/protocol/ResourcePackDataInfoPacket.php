<?php

namespace pocketmine\network\protocol;

use pocketmine\network\multiversion\MultiversionEnums;

class ResourcePackDataInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACK_DATA_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_DATA_INFO_PACKET";

	const MAX_CHUNK_SIZE = 1048576; // 1MB

	const TYPE_INVALID = 'TYPE_INVALID';
	const TYPE_ADDON = 'TYPE_ADDON';
	const TYPE_CACHED = 'TYPE_CACHED';
	const TYPE_COPY_PROTECTED = 'TYPE_COPY_PROTECTED';
	const TYPE_BEHAVIOR = 'TYPE_BEHAVIOR';
	const TYPE_PERSONA_PIECE = 'TYPE_PERSONA_PIECE';
	const TYPE_RESOURCE = 'TYPE_RESOURCE';
	const TYPE_SKINS = 'TYPE_SKINS';
	const TYPE_WORLD_TEMPLATE = 'TYPE_WORLD_TEMPLATE';
	const TYPE_COUNT = 'TYPE_COUNT';	
	
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
			$this->putByte(MultiversionEnums::getPackTypeId($playerProtocol, $this->type));
		}
	}

}

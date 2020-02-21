<?php

namespace pocketmine\network\protocol;

use pocketmine\mods\Addon;
use pocketmine\mods\ResourcePack;

class ResourcePacksInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_INFO_PACKET";

	/** @var boolean */
	public $isRequired = false;
	public $hasScripts = false;
	/** @var Addon[] */
	public $addons = [];
	/** @var ResourcePack[] */
	public $resourcePacks = [];
	
	// read
	public function decode($playerProtocol) {
		
	}
	
	// write
	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->isRequired);
		$this->putByte($this->hasScripts);
		$this->putLShort(count($this->addons));
		foreach ($this->addons as $addon) {
			$this->putString($addon->id);
			$this->putString($addon->version);
			$this->putLLong($addon->size);
			$this->putString($addon->contentKey);
			$this->putString($addon->subPackName);
			if ($playerProtocol >= Info::PROTOCOL_280) {
				$this->putString(''); // content identity
			}
			if ($playerProtocol >= Info::PROTOCOL_331) {
				$this->putByte(0); // has scripts
			}
		}
		$this->putLShort(count($this->resourcePacks));
		foreach ($this->resourcePacks as $resourcePack) {
			$this->putString($resourcePack->id);
			$this->putString($resourcePack->version);
			$this->putLLong($resourcePack->size);
			$this->putString($resourcePack->contentKey);
			$this->putString($resourcePack->subPackName);
			if ($playerProtocol >= Info::PROTOCOL_280) {
				$this->putString(''); // content identity
			}
			if ($playerProtocol >= Info::PROTOCOL_331) {
				$this->putByte(0); // has scripts
			}
		}		
	}

}

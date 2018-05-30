<?php

namespace pocketmine\network\protocol;

use pocketmine\mods\Addon;
use pocketmine\mods\ResourcePack;

class ResourcePacksInfoPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_INFO_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_INFO_PACKET";

	/** @var boolean */
	public $isRequired = false;
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
		$this->putLShort(count($this->addons));
		foreach ($this->addons as $addon) {
			$this->putString($addon->id);
			$this->putString($addon->version);
			$this->putLLong($addon->size);
			$this->putString($addon->contentKey);
			$this->putString($addon->subPackName);
		}
		$this->putLShort(count($this->resourcePacks));
		foreach ($this->resourcePacks as $resourcePack) {
			$this->putString($resourcePack->id);
			$this->putString($resourcePack->version);
			$this->putLLong($resourcePack->size);
			$this->putString($resourcePack->contentKey);
			$this->putString($resourcePack->subPackName);
		}
	}

}

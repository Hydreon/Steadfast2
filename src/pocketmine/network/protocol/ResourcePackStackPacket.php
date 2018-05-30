<?php

namespace pocketmine\network\protocol;

class ResourcePackStackPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_STACK_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_STACK_PACKET";

	/** @var boolean */
	public $isRequired = false;
	/** @var Addon[] */
	public $addons = [];
	/** @var ResourcePack[] */
	public $resourcePacks = [];

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->isRequired);
		$this->putVarInt(count($this->addons));
		foreach ($this->addons as $addon) {
			$this->putString($addon->id);
			$this->putString($addon->version);
			$this->putString($addon->subPackName);
		}
		$this->putVarInt(count($this->resourcePacks));
		foreach ($this->resourcePacks as $resourcePack) {
			$this->putString($resourcePack->id);
			$this->putString($resourcePack->version);
			$this->putString($resourcePack->subPackName);
		}
	}

}

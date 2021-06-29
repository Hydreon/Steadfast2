<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class PlayerSkinPacket extends PEPacket {

	const NETWORK_ID = Info331::PLAYER_SKIN_PACKET;
	const PACKET_NAME = "PLAYER_SKIN_PACKET";

	public $uuid;
	public $newSkinId;
	public $newSkinName;
	public $oldSkinName;
	public $newSkinByteData;
	public $newCapeByteData;
	public $newSkinGeometryName;
	public $newSkinGeometryData;
	public $isPremiumSkin = false;
	public $additionalSkinData = [];
	public $isTrustedSkin;
	

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->uuid = $this->getUUID();
		$this->getSerializedSkin($playerProtocol, $this->newSkinId, $this->newSkinByteData, $this->newSkinGeometryName, $this->newSkinGeometryData, $this->newCapeByteData, $this->additionalSkinData);
		if (isset($this->additionalSkinData['PremiumSkin']) && $this->additionalSkinData['PremiumSkin']) {
			$this->isPremiumSkin = true;
		}
		$this->newSkinName = $this->getString();
		$this->oldSkinName = $this->getString();
		$this->isTrustedSkin = $this->getByte(); //whether skin trusted marketplace content
		$this->checkSkinData($this->newSkinByteData, $this->newSkinGeometryName, $this->newSkinGeometryData, $this->additionalSkinData);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putUUID($this->uuid);
		$this->putSerializedSkin($playerProtocol, $this->newSkinId, $this->newSkinByteData, $this->newSkinGeometryName, $this->newSkinGeometryData, $this->newCapeByteData, $this->additionalSkinData);
		$this->putString($this->newSkinName);
		$this->putString($this->oldSkinName);
		$this->putByte($this->isTrustedSkin); // is trusted skin
	}
}
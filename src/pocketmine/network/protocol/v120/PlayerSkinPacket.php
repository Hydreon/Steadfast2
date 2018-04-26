<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class PlayerSkinPacket extends PEPacket {

	const NETWORK_ID = Info120::PLAYER_SKIN_PACKET;
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


	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->uuid = $this->getUUID();
		$this->newSkinId = $this->getString();
		$this->newSkinName = $this->getString();
		$this->oldSkinName = $this->getString();
		if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
			$this->getLInt(); // num skin data, always 1
			$this->getLInt();
		}
		$this->newSkinByteData = $this->getString();
		if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
			$this->getLInt();
			$this->getLInt();
			$this->newCapeByteData = $this->getString();
		} else {
			$this->newCapeByteData = $this->getString();
		}
		$this->newSkinGeometryName = $this->getString();
		$this->newSkinGeometryData = $this->getString();
		if ($playerProtocol >= Info::PROTOCOL_260) {
			$this->isPremiumSkin = $this->getByte();
		}
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putUUID($this->uuid);
		$this->putString($this->newSkinId);
		$this->putString($this->newSkinName);
		$this->putString($this->oldSkinName);
		if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
			$this->putLInt(1); // num skin data, always 1
			$this->putLInt(strlen($this->newSkinByteData));
		}
		$this->putString($this->newSkinByteData);
		if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
			$this->putLInt(empty($this->newCapeByteData));
			$this->putLInt(strlen($this->newCapeByteData));
			$this->putString($this->newCapeByteData);
		} else {
			$this->putString($this->newCapeByteData);
		}
		$this->putString($this->newSkinGeometryName);
		$this->putString($this->newSkinGeometryData);
		if ($playerProtocol >= Info::PROTOCOL_260) {
			$this->putByte($this->isPremiumSkin);
		}
	}
}
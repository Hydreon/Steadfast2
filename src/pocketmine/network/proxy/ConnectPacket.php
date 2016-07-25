<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;
use pocketmine\network\protocol\DataPacket;

class ConnectPacket extends DataPacket {

	const NETWORK_ID = Info::CONNECT_PACKET;

	public $identifier;
	public $additionalChar;
	public $protocol;
	public $clientId;
	public $clientUUID;
	public $clientSecret;
	public $username;
	public $skinName;
	public $skin;

	public function decode() {
		$this->identifier = $this->getString();
		$this->additionalChar = $this->getByte();
		$this->protocol = $this->getInt();
		$this->clientId = $this->getLong();
		$this->clientUUID = $this->getUUID();
		$this->clientSecret = $this->getString();
		$this->username = $this->getString();
		$this->skinName = $this->getString();
		$this->skin = $this->getString();		
		
	}

	public function encode() {
		$this->reset();
		$this->putString($this->identifier);
		$this->putByte($this->additionalChar);
		$this->putInt($this->protocol);
		$this->putLong($this->clientId);
		$this->putUUID($this->clientUUID);
		$this->putString($this->clientSecret);
		$this->putString($this->username);
		$this->putString($this->skinName);
		$this->putString($this->skin);
	}

}

<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;

class ConnectPacket extends ProxyPacket {

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
	public $viewDistance;
	public $ip;
	public $port;

	public function decode() {
		$this->identifier = $this->getString();
		$this->additionalChar = chr($this->getByte());
		$this->protocol = $this->getInt();
		$this->clientId = $this->getLong();
		$this->clientUUID = $this->getUUID();
		$this->clientSecret = $this->getString();
		$this->username = $this->getString();
		$this->skinName = $this->getString();
		$this->skin = $this->getString();
		$this->viewDistance = $this->getInt();
		$this->ip = $this->getString();
		$this->port = $this->getInt();
	}

	public function encode() {
		$this->reset();
		$this->putString($this->identifier);
		$this->putByte(ord($this->additionalChar));
		$this->putInt($this->protocol);
		$this->putLong($this->clientId);
		$this->putUUID($this->clientUUID);
		$this->putString($this->clientSecret);
		$this->putString($this->username);
		$this->putString($this->skinName);
		$this->putString($this->skin);
		$this->putInt($this->viewDistance);
		$this->putString($this->ip);
		$this->putInt($this->port);
	}

}

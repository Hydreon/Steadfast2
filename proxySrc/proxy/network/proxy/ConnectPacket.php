<?php

namespace proxy\network\proxy;

use proxy\network\proxy\Info;

class ConnectPacket extends ProxyPacket {

	const NETWORK_ID = Info::CONNECT_PACKET;

	public $identifier;
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
		
	}

	public function encode() {
		$this->reset();
		$this->putString($this->identifier);
		$this->putInt($this->protocol);
		$this->putString($this->clientId);
		$this->putString($this->clientSecret);
		$this->putString($this->username);
		$this->putString($this->skinName);
		$this->putString($this->skin);
		$this->putInt($this->viewDistance);
		$this->putString($this->ip);
		$this->putInt($this->port);
	}

}

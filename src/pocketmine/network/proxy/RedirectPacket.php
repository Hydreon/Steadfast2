<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;

class RedirectPacket extends ProxyPacket {

	const NETWORK_ID = Info::REDIRECT_PACKET;

	public $ip;
	public $port;
	public $data;

	public function decode() {
		$this->ip = $this->getString();
		$this->port = $this->getInt();
		$this->data = $this->getString();
	}

	public function encode() {
		$this->reset();
		$this->putString($this->ip);
		$this->putInt($this->port);
		$this->putString($this->data);
	}

}

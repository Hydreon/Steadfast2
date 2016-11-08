<?php

namespace proxy\network\proxy;

use proxy\network\proxy\Info;

class RedirectPacket extends ProxyPacket {

	const NETWORK_ID = Info::REDIRECT_PACKET;

	public $ip;
	public $port;

	public function decode() {
		$this->ip = $this->getString();
		$this->port = $this->getInt();
	}

	public function encode() {
		$this->reset();
		$this->putString($this->ip);
		$this->putInt($this->port);
	}

}

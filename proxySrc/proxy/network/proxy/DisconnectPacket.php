<?php

namespace proxy\network\proxy;

use proxy\network\proxy\Info;

class DisconnectPacket extends ProxyPacket {

	const NETWORK_ID = Info::DISCONNECT_PACKET;

	public $reason;

	public function decode() {
		$this->reason = $this->getString();
	}

	public function encode() {
		$this->reset();
		$this->putString($this->reason);
	}

}

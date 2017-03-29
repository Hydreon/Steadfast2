<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;

class PingPacket extends ProxyPacket {

	const NETWORK_ID = Info::PING_PACKET;

	public $ping;

	public function decode() {
		$this->ping = $this->getVarInt();
	}

	public function encode() {
		$this->reset();
		$this->putVarInt($this->ping);
	}

}

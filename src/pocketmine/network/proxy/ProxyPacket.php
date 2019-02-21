<?php

namespace pocketmine\network\proxy;

use pocketmine\network\protocol\DataPacket;

abstract class ProxyPacket extends DataPacket {

	const NETWORK_ID = 0;

	public function pid() {
		return $this::NETWORK_ID;
	}

	abstract public function encode();

	abstract public function decode();

	public function reset() {
		$this->buffer = chr($this::NETWORK_ID);
		$this->offset = 0;
	}
}

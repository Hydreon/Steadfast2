<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;

class DisconnectCompletePacket extends ProxyPacket {

	const NETWORK_ID = Info::DISCONNECT_COMPLETE_PACKET;

	public function decode() {
		
	}

	public function encode() {
		$this->reset();
	}

}

<?php

namespace pocketmine\network\protocol;

class ServerToClientHandshakePacket extends DataPacket {

	const NETWORK_ID = Info::SERVER_TO_CLIENT_HANDSHAKE_PACKET;

	public $publicKey;
	public $serverToken;

	public function decode() {
		
	}

	public function encode() {
		$this->reset();
		$this->putString($this->publicKey);
		$this->putString($this->serverToken);
	}

}

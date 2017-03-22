<?php

namespace pocketmine\network\protocol;

class ServerToClientHandshakePacket extends PEPacket {

	const NETWORK_ID = Info::SERVER_TO_CLIENT_HANDSHAKE_PACKET;
	const PACKET_NAME = "SERVER_TO_CLIENT_HANDSHAKE_PACKET";

	public $publicKey;
	public $serverToken;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->publicKey);
		$this->putString($this->serverToken);
	}

}

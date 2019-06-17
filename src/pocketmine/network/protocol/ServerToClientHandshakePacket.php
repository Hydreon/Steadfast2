<?php

namespace pocketmine\network\protocol;

use pocketmine\utils\JWT;

class ServerToClientHandshakePacket extends PEPacket {

	const NETWORK_ID = Info::SERVER_TO_CLIENT_HANDSHAKE_PACKET;
	const PACKET_NAME = "SERVER_TO_CLIENT_HANDSHAKE_PACKET";

	public $publicKey;
	public $serverToken;
	public $privateKey;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$header = ['alg' => 'ES384', 'x5u' => $this->publicKey];
		$payload = ['salt' => JWT::base64UrlEncode($this->serverToken)];
		$jwt = JWT::createJwt($header, $payload, $this->privateKey);
		$this->putString($jwt);
	}

}

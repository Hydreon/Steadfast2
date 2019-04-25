<?php

namespace pocketmine\network\protocol\v360;

use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class ClientCacheMissResponsePacket extends PEPacket {

	const NETWORK_ID = Info331::CLIENT_CACHE_MISS_RESPONSE_PACKET;
	const PACKET_NAME = "CLIENT_CACHE_MISS_RESPONSE_PACKET";

	public $data = [];

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt(count($this->data));
		foreach ($this->data as $hash => $data) {
			$this->put($hash);
			$this->putString($data);
		}
	}

	public function decode($playerProtocol) {
		
	}

}

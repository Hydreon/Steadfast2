<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class AvailableEntityIdentifiersPacket extends PEPacket {

	const NETWORK_ID = Info310::AVAILABLE_ENTITY_IDENTIFIERS_PACKET;
	const PACKET_NAME = "AVAILABLE_ENTITY_IDENTIFIERS_PACKET";

	public $namedtag;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->namedtag = $this->get(true);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->put($this->namedtag);
	}

}

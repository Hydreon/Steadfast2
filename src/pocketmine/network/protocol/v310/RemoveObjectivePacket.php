<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class RemoveObjectivePacket extends PEPacket {

	const NETWORK_ID = Info310::REMOVE_OBJECTIVE_PACKET;
	const PACKET_NAME = "REMOVE_OBJECTIVE_PACKET";

	public $objectiveName;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->objectiveName);
	}

	public function decode($playerProtocol) {
		
	}

}

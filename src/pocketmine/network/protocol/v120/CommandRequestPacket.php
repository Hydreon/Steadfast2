<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class CommandRequestPacket extends PEPacket {
	
	const NETWORK_ID = Info120::COMMAND_REQUEST_PACKET;
	const PACKET_NAME = "COMMAND_REQUEST_PACKET";
	
	/** @var string */
	public $command = '';
	
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->command = $this->getString();
	}

	public function encode($playerProtocol) {
	}
	
}

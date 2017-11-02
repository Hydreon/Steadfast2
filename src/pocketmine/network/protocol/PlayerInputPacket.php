<?php

namespace pocketmine\network\protocol;

class PlayerInputPacket extends PEPacket {

	const NETWORK_ID = Info::PLAYER_INPUT_PACKET;
	const PACKET_NAME = "PLAYER_INPUT_PACKET";

	public $forward;
	public $sideway;
	public $jump;
	public $sneak;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->forward = $this->getLFloat();
		$this->sideway = $this->getLFloat();
		$this->jump = $this->getByte();
		$this->sneak = $this->getByte();
	}

	public function encode($playerProtocol) {
		
	}

}

<?php

namespace pocketmine\network\protocol;

class SpawnExperienceOrbPacket extends PEPacket {

	const NETWORK_ID = Info::SPAWN_EXPERIENCE_ORB_PACKET;
	const PACKET_NAME = "SPAWN_EXPERIENCE_ORB_PACKET";
	
	public $count = 2;
	public $x;
	public $y;
	public $z;

	public function decode($playerProtocol) {

	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putVarInt($this->count);

	}

}

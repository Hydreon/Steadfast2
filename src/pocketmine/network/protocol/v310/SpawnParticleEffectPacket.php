<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class SpawnParticleEffectPacket extends PEPacket {

	const NETWORK_ID = Info310::SPAWN_PARTICLE_EFFECT_PACKET;
	const PACKET_NAME = "SPAWN_PARTICLE_EFFECT_PACKET";

	public $dimensionId = 0;
	public $entityUniqueId = 1;
	public $x;
	public $y;
	public $z;
	public $particleName;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->dimensionId = $this->getByte();
		if ($playerProtocol >= Info::PROTOCOL_330) {
			$this->entityUniqueId = $this->getVarInt();
		}
		$this->x = $this->getLFloat();
		$this->y = $this->getLFloat();
		$this->z = $this->getLFloat();
		$this->particleName = $this->getString();
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->dimensionId);
		if ($playerProtocol >= Info::PROTOCOL_330) {
			$this->putVarInt($this->entityUniqueId);
		}
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putString($this->particleName);
	}

}

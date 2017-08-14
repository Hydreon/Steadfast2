<?php

namespace pocketmine\network\protocol;

class LevelSoundEventPacket extends PEPacket {

	const NETWORK_ID = Info::LEVEL_SOUND_EVENT_PACKET;
	const PACKET_NAME = "LEVEL_SOUND_EVENT_PACKET";
	
	const SOUND_HIT = 1;
	const SOUND_BREAK = 4;
	const SOUND_PLACE = 5;
	const SOUND_EAT = 30;
 	const SOUND_EXPLODE = 45;
	const SOUND_BREAK_BLOCK = 52;
 	const SOUND_CHEST_OPEN = 60;
 	const SOUND_CHEST_CLOSED = 61;
	const SOUND_NOTE = 72;

	public $eventId;
	public $x;
	public $y;
	public $z;
	public $blockId = -1;
	public $entityType = 1;
	public $babyMob = 0;
	public $global = 0;

	public function decode($playerProtocol) {
		$this->eventId = $this->getByte();
		$this->x = $this->getLFloat();
		$this->y = $this->getLFloat();
		$this->z = $this->getLFloat();
		$this->blockId = $this->getSignedVarInt();
		$this->entityType = $this->getSignedVarInt();
		$this->babyMob = $this->getByte();
		$this->global = $this->getByte();
	}

	public function encode($playerProtocol) {
		if ($playerProtocol < Info::PROTOCOL_110 && $this->eventId == self::SOUND_NOTE) {
			$this->eventId = 70;
		}
		$this->reset($playerProtocol);
		$this->putByte($this->eventId);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putSignedVarInt($this->blockId);
		$this->putSignedVarInt($this->entityType);
		$this->putByte($this->babyMob);
		$this->putByte($this->global);
	}

}

<?php

namespace pocketmine\network\protocol;

use pocketmine\network\multiversion\MultiversionEnums;

class LevelSoundEventPacket extends PEPacket {

	const NETWORK_ID = Info::LEVEL_SOUND_EVENT_PACKET;
	const PACKET_NAME = "LEVEL_SOUND_EVENT_PACKET";
	
	const SOUND_HIT = 'SOUND_HIT';
	const SOUND_BREAK = 'SOUND_BREAK';
	const SOUND_PLACE = 'SOUND_PLACE';
	const SOUND_EAT = 'SOUND_EAT';
 	const SOUND_EXPLODE = 'SOUND_EXPLODE';
	const SOUND_BREAK_BLOCK = 'SOUND_BREAK_BLOCK';
 	const SOUND_CHEST_OPEN = 'SOUND_CHEST_OPEN';
 	const SOUND_CHEST_CLOSED = 'SOUND_CHEST_CLOSED';
	const SOUND_NOTE = 'SOUND_NOTE';
	const SOUND_BOW = 'SOUND_BOW';
	const SOUND_UNDEFINED = 'SOUND_UNDEFINED';

	public $eventId;
	public $x;
	public $y;
	public $z;
	public $blockId = -1;
	public $entityType = 1;
	public $babyMob = 0;
	public $global = 0;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->eventId = $this->getByte();
		$this->eventId = MultiversionEnums::getLevelSoundEventName($playerProtocol, $this->eventId);
		$this->x = $this->getLFloat();
		$this->y = $this->getLFloat();
		$this->z = $this->getLFloat();
		if ($playerProtocol >= Info::PROTOCOL_220) {
			$runtimeId = $this->getSignedVarInt();
			$blockData = self::getBlockIDByRuntime($runtimeId, $playerProtocol);
			$this->blockId = $blockData[0];
		} else {
			$this->blockId = $this->getSignedVarInt();
		}		
		$this->entityType = $this->getSignedVarInt();
		$this->babyMob = $this->getByte();
		$this->global = $this->getByte();
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$eventId = MultiversionEnums::getLevelSoundEventId($playerProtocol, $this->eventId);
		$this->putByte($eventId);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		if ($playerProtocol >= Info::PROTOCOL_220) {
			$runtimeId = self::getBlockRuntimeID($this->blockId, 0, $playerProtocol);
			$this->putSignedVarInt($runtimeId);
		} else {
			$this->putSignedVarInt($this->blockId);
		}
		$this->putSignedVarInt($this->entityType);
		$this->putByte($this->babyMob);
		$this->putByte($this->global);
	}

}

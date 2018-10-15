<?php

namespace pocketmine\network\protocol\Info310;

use pocketmine\network\multiversion\Entity;
use pocketmine\network\multiversion\MultiversionEnums;
use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class LevelSoundEventPacket extends PEPacket {

	const NETWORK_ID = Info310::LEVEL_SOUND_EVENT_PACKET;
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
		$runtimeId = $this->getSignedVarInt();
		if ($runtimeId < 0) {
			$this->blockId = -1;
		} else {
			$blockData = self::getBlockIDByRuntime($runtimeId, $playerProtocol);
			$this->blockId = $blockData[0];
		}
		$entityName = $this->getString();
		$this->entityType = Entity::getIDByName($entityName);
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
		if ($this->blockId < 0) {
			$this->putSignedVarInt($this->blockId);
		} else {
			$runtimeId = self::getBlockRuntimeID($this->blockId, 0, $playerProtocol);
			$this->putSignedVarInt($runtimeId);
		}
		$this->putString(Entity::getNameByID($this->entityType));
		$this->putByte($this->babyMob);
		$this->putByte($this->global);
	}

}

<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\network\protocol;

class LevelSoundEventPacket extends PEPacket {

	const NETWORK_ID = Info::LEVEL_SOUND_EVENT_PACKET;
	const PACKET_NAME = "LEVEL_SOUND_EVENT_PACKET";
	
	const SOUND_NOTE = 72;

	public $eventId;
	public $x;
	public $y;
	public $z;
	public $blockId = 0;
	public $entityType = 1;
	public $babyMob = 0;
	public $global = 0;

	public function decode($playerProtocol) {
		
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


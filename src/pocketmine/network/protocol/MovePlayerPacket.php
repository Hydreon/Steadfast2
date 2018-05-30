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

#include <rules/DataPacket.h>


class MovePlayerPacket extends PEPacket{
	const NETWORK_ID = Info::MOVE_PLAYER_PACKET;
	const PACKET_NAME = "MOVE_PLAYER_PACKET";

	const MODE_NORMAL = 0;
	const MODE_RESET = 1;
	const MODE_TELEPORT = 2;
	const MODE_ROTATION = 3;
	
	const TELEPORTATION_CAUSE_UNKNOWN = 0;
	const TELEPORTATION_CAUSE_PROJECTILE = 1;
	const TELEPORTATION_CAUSE_CHORUS_FRUIT = 2;
	const TELEPORTATION_CAUSE_COMMAND = 3;
	const TELEPORTATION_CAUSE_BEHAVIOR = 4;
	const TELEPORTATION_CAUSE_COUNT = 5; // ???

	public $eid;
	public $x;
	public $y;
	public $z;
	public $yaw;
	public $bodyYaw;
	public $pitch;
	public $mode = self::MODE_NORMAL;
	public $onGround;

	public function clean(){
		$this->teleport = false;
		return parent::clean();
	}

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		
		$this->x = $this->getLFloat();
		$this->y = $this->getLFloat();
		$this->z = $this->getLFloat();
		
		$this->pitch = $this->getLFloat();
		$this->yaw = $this->getLFloat();
		
		$this->bodyYaw = $this->getLFloat();
		$this->mode = $this->getByte();
		$this->onGround = $this->getByte() > 0;
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		
		$this->putLFloat($this->bodyYaw);
		$this->putByte($this->mode);
		$this->putByte($this->onGround > 0);
		/** @todo do it right */
		$this->putVarInt(0); // riding runtime ID
		if (self::MODE_TELEPORT == $this->mode) {
			$this->putInt(self::TELEPORTATION_CAUSE_UNKNOWN);
			$this->putInt(1);
		}
	}

}

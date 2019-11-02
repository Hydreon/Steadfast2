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


class LevelEventPacket extends PEPacket{
	const NETWORK_ID = Info::LEVEL_EVENT_PACKET;
	const PACKET_NAME = "LEVEL_EVENT_PACKET";

	const EVENT_SOUND_CLICK = 1000;
	const EVENT_SOUND_CLICK_FAIL = 1001;
	const EVENT_SOUND_SHOOT = 1002;
	const EVENT_SOUND_DOOR = 1003;
	const EVENT_SOUND_FIZZ = 1004;

	const EVENT_SOUND_GHAST = 1007;
	const EVENT_SOUND_GHAST_SHOOT = 1008;
	const EVENT_SOUND_BLAZE_SHOOT = 1009;

	const EVENT_SOUND_DOOR_BUMP = 1010;
	const EVENT_SOUND_DOOR_CRASH = 1012;

	const EVENT_SOUND_BAT_FLY = 1015;
	const EVENT_SOUND_ZOMBIE_INFECT = 1016;
	const EVENT_SOUND_ZOMBIE_HEAL = 1017;
	const EVENT_SOUND_ENDERMAN_TELEPORT = 1018;

	const EVENT_SOUND_ANVIL_BREAK = 1020;
	const EVENT_SOUND_ANVIL_USE = 1021;
	const EVENT_SOUND_ANVIL_FALL = 1022;

	const EVENT_PARTICLE_SHOOT = 2000;
	const EVENT_PARTICLE_DESTROY = 2001;
	const EVENT_PARTICLE_SPLASH = 2002;
	const EVENT_PARTICLE_EYE_DESPAWN = 2003;
	const EVENT_PARTICLE_SPAWN = 2004;
	const EVENT_PARTICLE_CRACK_BLOCK = 2014;
	
	// couldron 3501 - 3508
	
	const EVENT_START_RAIN = 3001;
	const EVENT_START_THUNDER = 3002;
	const EVENT_STOP_RAIN = 3003;
	const EVENT_STOP_THUNDER = 3004;
	
	const EVENT_START_BLOCK_CRACKING = 3600;
	const EVENT_STOP_BLOCK_CRACKING = 3601;
	
	const EVENT_SET_DATA = 4000;
	const EVENT_PLAYERS_SLEEPING = 9800;
	const EVENT_ADD_PARTICLE_MASK = 0x4000;

	public $evid;
	public $x;
	public $y;
	public $z;
	public $data = 0;

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->evid);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		if ($playerProtocol >= Info::PROTOCOL_220) {
			switch ($this->evid) {
				case self::EVENT_PARTICLE_DESTROY:
				case self::EVENT_PARTICLE_CRACK_BLOCK:
					$id = $this->data & 0xff;
					$meta = ($this->data >> 8) & 0x0f;
					$runtimeId = self::getBlockRuntimeID($id, $meta, $playerProtocol);
					$this->putSignedVarInt($runtimeId);
					break;
				default :
					$this->putSignedVarInt($this->data);
					break;
			}
		} else {		
			$this->putSignedVarInt($this->data);
		}
	}

}

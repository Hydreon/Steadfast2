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


class StartGamePacket extends PEPacket{
	const NETWORK_ID = Info::START_GAME_PACKET;
	const PACKET_NAME = "START_GAME_PACKET";

	public $seed;
	public $dimension;
	public $generator = 1;
	public $gamemode;
	public $eid;
	public $spawnX;
	public $spawnY;
	public $spawnZ;
	public $x;
	public $y;
	public $z;

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid); //EntityUniqueID
		$this->putVarInt($this->eid); //EntityUniqueID
		
		if ($playerProtocol >= Info::PROTOCOL_110) {
 			$this->putSignedVarInt($this->gamemode);	// Entity gamemode
 		}
		
		$this->putLFloat($this->x); // default position (4)
		$this->putLFloat($this->y); // (4)
		$this->putLFloat($this->z); // (4)
		
		$this->putLFloat(0);
		$this->putLFloat(0);
		
		// Level settings
		
		$this->putSignedVarInt($this->seed);
		
		$this->putSignedVarInt($this->dimension);
		
		$this->putSignedVarInt($this->generator);
		
		$this->putSignedVarInt($this->gamemode);
		
		$this->putSignedVarInt(0); // Difficulty
		
		// default spawn 3x VarInt
		$this->putSignedVarInt($this->spawnX);
		$this->putSignedVarInt($this->spawnY);
		$this->putSignedVarInt($this->spawnZ);
		
		$this->putByte(1); // hasAchievementsDisabled
		
		$this->putSignedVarInt(0); // DayCycleStopTyme 1x VarInt
		
		$this->putByte(0); //edu mode

		$this->putLFloat(0); //rain level

		$this->putLFloat(0); //lightning level
		
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte(1); // is multiplayer game
			$this->putByte(1); // Broadcast to LAN?
			$this->putByte(1); // Broadcast to XBL?
		}
		
		$this->putByte(1);	//commands enabled
		
		$this->putByte(0); // isTexturepacksRequired 1x Byte
		
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putVarInt(0); // rules count
			$this->putByte(0); // is bonus chest enabled
			$this->putByte(0); // is start with map enabled
			$this->putByte(0); // has trust players enabled
			$this->putSignedVarInt(1); // permission level
			$this->putSignedVarInt(4); // game publish setting
			$this->putString('3138ee93-4a4a-479b-8dca-65ca5399e075'); // level id (random UUID)
			$this->putString(''); // level name
			$this->putString(''); // template pack id
			$this->putByte(0); // is trial?
			$this->putLong(0); // current level time
			$this->putSignedVarInt(0); // enchantment seed
		}
	}

}

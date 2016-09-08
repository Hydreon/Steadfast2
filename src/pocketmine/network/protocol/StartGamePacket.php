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


class StartGamePacket extends DataPacket{
	const NETWORK_ID = Info::START_GAME_PACKET;

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

	public function decode(){

	}

	public function encode(){
		$this->reset();
		$this->putVarInt(0); //EntityUniqueID
		$this->putVarInt($this->eid); //EntityUniqueID
		
		$this->putLFloat($this->x); // default position (4)
		$this->putLFloat($this->y); // (4)
		$this->putLFloat($this->z); // (4)
		
		$this->putSignedVarInt($this->seed);
		
		$this->putSignedVarInt($this->dimension);
		
		$this->putSignedVarInt($this->generator);
		
		$this->putSignedVarInt($this->gamemode);
		
		$this->putSignedVarInt(0); // Difficulty, i don't know how use it
		
		// default spawn 3x VarInt
		$this->putSignedVarInt(0);
		$this->putSignedVarInt(0);
		$this->putSignedVarInt(0);
//		$this->putVarInt(abs($this->spawnX));
//		$this->putVarInt(abs($this->spawnY));
//		$this->putVarInt(abs($this->spawnZ));
		
		$this->putByte(0); //has been loaded in creative (1)
		
		$this->putSignedVarInt(0); // DayCycleStopTyme 1x VarInt
		
		$this->putByte(0); //edu mode the same type as loaded in creative (1)

		$this->putLFloat(0); //rain level the same type as loaded in creative (4 bytes)

		$this->putLFloat(0); //lightning level the same type as loaded in creative (4 bytes)
		
		$this->putByte(1);	//commands enabled the same type as loaded in creative (1)
//		$this->putString('iX8AANxLbgA=');
	}

}

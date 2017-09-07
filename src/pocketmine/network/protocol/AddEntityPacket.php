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

#ifndef COMPILE
use pocketmine\utils\Binary;

#endif

class AddEntityPacket extends PEPacket{
	const NETWORK_ID = Info::ADD_ENTITY_PACKET;
	const PACKET_NAME = "ADD_ENTITY_PACKET";

	public $eid;
	public $type;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;
	public $yaw;
	public $pitch;
	public $metadata = [];
	public $links = [];
	public $attributes = [];

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);		
		$this->putVarInt($this->eid);
		$this->putVarInt($this->eid);
		$this->putVarInt($this->type);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putLFloat($this->speedX);
		$this->putLFloat($this->speedY);
		$this->putLFloat($this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putVarInt(count($this->attributes));
		foreach ($this->attributes as $attribute) {
			$this->putString($attribute['name']);
			$this->putLFloat($attribute['min']);
			$this->putLFloat($attribute['default']);
			$this->putLFloat($attribute['max']);			
		}
		$meta = Binary::writeMetadata($this->metadata, $playerProtocol);
		$this->put($meta);

		$this->putVarInt(count($this->links));
		foreach ($this->links as $link) {
			$this->putVarInt($link['from']);
			$this->putVarInt($link['to']);
			$this->putByte($link['type']);
			$this->putByte(0);
		}
	}
}
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

use pocketmine\utils\Binary;

class AddItemEntityPacket extends PEPacket{
	const NETWORK_ID = Info::ADD_ITEM_ENTITY_PACKET;
	const PACKET_NAME = "ADD_ITEM_ENTITY_PACKET";

	public $eid;
	public $item;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;
	public $metadata = [];

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putEntityUniqueId($this->eid);
		$this->putEntityRuntimeId($this->eid);
		$this->putSlot($this->item, $playerProtocol);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putLFloat($this->speedX);
		$this->putLFloat($this->speedY);
		$this->putLFloat($this->speedZ);
		$meta = Binary::writeMetadata($this->metadata, $playerProtocol);
		$this->put($meta);
		$this->putByte(0); // isFromFishing
	}

}

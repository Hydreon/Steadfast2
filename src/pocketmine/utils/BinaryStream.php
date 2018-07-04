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

namespace pocketmine\utils;

#include <rules/DataPacket.h>

#ifndef COMPILE

#endif

use pocketmine\item\Item;
use pocketmine\network\protocol\Info;

class BinaryStream extends \MCBinaryStream {
// class BinaryStream {

	public function getUUID() {
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();
		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) {
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSlot($playerProtocol){		
		$id = $this->getSignedVarInt();		
		if($id <= 0){
			return Item::get(Item::AIR, 0, 0);
		}
	
		$aux = $this->getSignedVarInt();
		$meta = $aux >> 8;
		$count = $aux & 0xff;

		$nbtLen = $this->getLShort();		
		$nbt = "";		
		if($nbtLen > 0){
			$nbt = $this->get($nbtLen);
		}
		// $this->offset += 2;
		$this->get(2);
		
		return Item::get($id, $meta, $count, $nbt);
	}

	public function putSlot(Item $item, $playerProtocol){
		if($item->getId() === 0){
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putSignedVarInt(($item->getDamage() === null ? 0  : ($item->getDamage() << 8)) + $item->getCount());	
		$nbt = $item->getCompound();	
		$this->putLShort(strlen($nbt));
		$this->put($nbt);
		$this->putByte(0);
		$this->putByte(0);
	}
	
}

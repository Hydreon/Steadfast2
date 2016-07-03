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

namespace pocketmine\nbt\tag;

use pocketmine\nbt\NBT;

class IntArray extends NamedTag{

	public function getType(){
		return NBT::TAG_IntArray;
	}

	public function read(NBT $nbt){
		$this->value = [];
		$size = $nbt->endianness === 1 ? (PHP_INT_SIZE === 8 ? unpack("N", $nbt->get(4))[1] << 32 >> 32 : unpack("N", $nbt->get(4))[1]) : (PHP_INT_SIZE === 8 ? unpack("V", $nbt->get(4))[1] << 32 >> 32 : unpack("V", $nbt->get(4))[1]);
		$this->value = unpack($nbt->endianness === NBT::LITTLE_ENDIAN ? "V*" : "N*", $nbt->get($size * 4));
	}

	public function write(NBT $nbt){
		$nbt->buffer .= $nbt->endianness === 1 ? pack("N", \count($this->value)) : pack("V", \count($this->value));
		$nbt->buffer .= pack($nbt->endianness === NBT::LITTLE_ENDIAN ? "V*" : "N*", ...$this->value);
	}
}
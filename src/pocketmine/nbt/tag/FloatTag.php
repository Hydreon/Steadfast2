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

#include <rules/NBT.h>

class FloatTag extends NamedTag{

	public function getType(){
		return NBT::TAG_Float;
	}

	public function read(NBT $nbt){
//		$this->value = $nbt->endianness === 1 ? (ENDIANNESS === 0 ? unpack("f", $nbt->get(4))[1] : unpack("f", strrev($nbt->get(4)))[1]) : (ENDIANNESS === 0 ? unpack("f", strrev($nbt->get(4)))[1] : unpack("f", $nbt->get(4))[1]);
		$this->value = $nbt->getFloat();
	}

	public function write(NBT $nbt){
//		$nbt->buffer .= $nbt->endianness === 1 ? (ENDIANNESS === 0 ? pack("f", $this->value) : strrev(pack("f", $this->value))) : (ENDIANNESS === 0 ? strrev(pack("f", $this->value)) : pack("f", $this->value));
		$nbt->putFloat($this->value);
	}
}
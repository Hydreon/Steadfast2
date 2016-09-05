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


use pocketmine\entity\Attribute;

class UpdateAttributesPacket extends DataPacket{
	const NETWORK_ID = Info::UPDATE_ATTRIBUTES_PACKET;

    const HEALTH = "generic.health";
    const HUNGER = "player.hunger";
    const EXPERIENCE = "player.experience";
    const EXPERIENCE_LEVEL = "player.level";


    public $entityId;

    public $minValue;
    public $maxValue;
    public $value;
    public $name;

	public function decode(){

	}

	public function encode(){
		$this->reset();

		$this->putVarInt($this->entityId);
		$this->putVarInt($this->entityId);

        $this->putLFloat($this->minValue);
        $this->putLFloat($this->maxValue);
        $this->putLFloat($this->value);
        $this->putString($this->name);
	}
}

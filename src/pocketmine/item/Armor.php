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


namespace pocketmine\item;

abstract class Armor extends Item{
    const TIER_NONE = 0;
    const TIER_LEATHER = 1;
	const TIER_GOLD = 2;
	const TIER_CHAIN = 3;
	const TIER_IRON = 4;
	const TIER_DIAMOND = 5;
    
	const TYPE_NONE = 0;
	const TYPE_HELMET = 1;
	const TYPE_CHESTPLATE = 2;
	const TYPE_LEGGINS = 3;
	const TYPE_BOOTS = 4;
    
    protected $type = Armor::TYPE_NONE;
    protected $tier = Armor::TIER_NONE;
    
    public function getType () {
        return $this->type;
    }
    
    public function getTier () {
        return $this->tier;
    }

	public function getMaxStackSize(){
		return 1;
	}
    
    
}
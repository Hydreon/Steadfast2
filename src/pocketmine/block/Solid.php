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

namespace pocketmine\block;

use pocketmine\block\redstoneBehavior\RedstoneComponent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Solid extends Block{

	const POWERED_NONE = 0;
	const POWERED_WEAKLY = 1;
	const POWERED_STRONGLY = 2;
	
	public function isSolid(){
		return true;
	}
	
	/** @todo */
	public function getPoweredState() {
		// !IMPORTANT! bottom should be first in the list
		static $offsets = [
			Vector3::SIDE_UP => [0, 1, 0],
			Vector3::SIDE_DOWN => [0, -1, 0],
			Vector3::SIDE_EAST => [1, 0, 0],
			Vector3::SIDE_WEST => [-1, 0, 0],
			Vector3::SIDE_SOUTH => [0, 0, 1],
			Vector3::SIDE_NORTH => [0, 0, -1],
		];
		foreach ($offsets as $side => $offset) {
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			switch ($blockId) {
				case self::REDSTONE_WIRE:
					if ($offset[1] == 0) { // all except up and down
						$wire = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
						if ($wire->getDamage() > 0) {
							return self::POWERED_WEAKLY;
						}
					}
					break;
				case self::REDSTONE_TORCH_ACTIVE;
					if ($offset[1] == -1) { // only bottom block
						return self::POWERED_STRONGLY;
					}
					break;
				case self::WOODEN_BUTTON:
				case self::STONE_BUTTON:
					$button = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
					if ($button->getFace() == $side && $button->isActive()) {
						return self::POWERED_STRONGLY;
					}
					break;
			}
		}
		return self::POWERED_NONE;
	}
}
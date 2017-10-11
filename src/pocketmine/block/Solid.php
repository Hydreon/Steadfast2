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

use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;

abstract class Solid extends Block{

	const POWERED_NONE = 0;
	const POWERED_WEAKLY = 1;
	const POWERED_STRONGLY = 2;
	
	public function isSolid() {
		return true;
	}
	
	public function onUpdate($type) {
		parent::onUpdate($type);
		static $offsets = [
			[0, 1, 0],
			[0, -1, 0],
			[1, 0, 0],
			[-1, 0, 0],
			[0, 0, 1],
			[0, 0, -1],
		];
		$pluginManager = Server::getInstance()->getPluginManager();
		$tmpVector = new Vector3();
		foreach ($offsets as $offset) {
			$tmpVector->setComponents($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			$block = $this->level->getBlock($tmpVector);
			if (in_array($block->getId(), self::REDSTONE_BLOCKS) || $block->getId() == self::IRON_DOOR_BLOCK) {
				$ev = new BlockUpdateEvent($block);
				$pluginManager->callEvent($ev);
				if(!$ev->isCancelled()){
					$ev->getBlock()->onUpdate(Level::BLOCK_UPDATE_NORMAL);
				}
			}
		}
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
		$poweredState = self::POWERED_NONE;
		foreach ($offsets as $side => $offset) {
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			switch ($blockId) {
				case self::REDSTONE_WIRE:
					if ($offset[1] == 0) { // all except up and down
						$wire = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
						if ($wire->getDamage() > 0) {
							$poweredState = self::POWERED_WEAKLY;
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
		return $poweredState;
	}
}
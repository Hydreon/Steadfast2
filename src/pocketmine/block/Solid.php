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
	
	protected $poweredStateDefined = false;

	public function isSolid() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		static $offsets = [
			[0, 1, 0],
			[0, -1, 0],
			[1, 0, 0],
			[-1, 0, 0],
			[0, 0, 1],
			[0, 0, -1],
		];
		$shouldUpdateBlocks = [
			self::REDSTONE_WIRE,
			self::REDSTONE_TORCH,
			self::REDSTONE_TORCH_ACTIVE,
			self::WOODEN_BUTTON,
			self::STONE_BUTTON,
			self::IRON_DOOR_BLOCK,
			self::DISPENSER,
			self::DROPPER,
			self::PISTON,
			self::STICKY_PISTON,
			self::REDSTONE_REPEATER_BLOCK,
			self::REDSTONE_REPEATER_BLOCK_ACTIVE,
		];
		$pluginManager = Server::getInstance()->getPluginManager();
		$tmpVector = new Vector3();
		foreach ($offsets as $offset) {
			$tmpVector->setComponents($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			$block = $this->level->getBlock($tmpVector);
			if (in_array($block->getId(), $shouldUpdateBlocks) || ($block->getId() == self::IRON_TRAPDOOR && $this->poweredStateDefined)) {
				$ev = new BlockUpdateEvent($block);
				$pluginManager->callEvent($ev);
				if(!$ev->isCancelled()){
					$ev->getBlock()->onUpdate(Level::BLOCK_UPDATE_NORMAL, $deep);
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
		$this->poweredStateDefined = true;
		$poweredState = self::POWERED_NONE;
		foreach ($offsets as $side => $offset) {
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			switch ($blockId) {
				case self::REDSTONE_WIRE:
					if ($offset[1] >= 0) { // all except down
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
				case self::LEVER:
				case self::WOODEN_BUTTON:
				case self::STONE_BUTTON:
					$element = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
					if ($element->getFace() == $side && $element->isActive()) {
						return self::POWERED_STRONGLY;
					}
					break;
				case self::WOODEN_PRESSURE_PLATE:
				case self::STONE_PRESSURE_PLATE:
				case self::WEIGHTED_PRESSURE_PLATE_LIGHT:
				case self::WEIGHTED_PRESSURE_PLATE_HEAVY:
					if ($side == Vector3::SIDE_UP) {
						$pressurePlate = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
						if ($pressurePlate->isActive()) {
							return self::POWERED_STRONGLY;
						}
					}
					break;
				case self::REDSTONE_REPEATER_BLOCK_ACTIVE:
					$repeater = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
					$frontBlockCoords = $repeater->getFrontBlockCoords();
					if ($this->x == $frontBlockCoords->x && $this->y == $frontBlockCoords->y && $this->z == $frontBlockCoords->z) {
						return self::POWERED_STRONGLY;
					}
					break;
			}
		}
		return $poweredState;
	}
}
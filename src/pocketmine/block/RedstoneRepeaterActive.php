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

use pocketmine\block\Block;
use pocketmine\block\Solid;
use pocketmine\level\Level;

class RedstoneRepeaterActive extends RedstoneRepeater {

	protected $id = self::REDSTONE_REPEATER_BLOCK_ACTIVE;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function onUpdate($type) {
		if ($type == Level::BLOCK_UPDATE_NORMAL) {
			$backPosition = $this->getBackBlockCoords();
			$backBlockID = $this->level->getBlockIdAt($backPosition->x, $backPosition->y, $backPosition->z);
			switch ($backBlockID) {
				case self::REDSTONE_WIRE:
					$wire = $this->level->getBlock($backPosition);
					if ($wire->meta > 0) {
						return;
					}
					break;
				case self::REDSTONE_TORCH_ACTIVE:
					return;
				case self::REDSTONE_REPEATER_BLOCK_ACTIVE:
					$activeRepeater = $this->level->getBlock($backPosition);
					if ($this->getFace() == $activeRepeater->getFace()) {
						return;
					}
					break;
				case self::WOODEN_BUTTON:
				case self::STONE_BUTTON:
				case self::LEVER:
				case self::WOODEN_PRESSURE_PLATE:
				case self::STONE_PRESSURE_PLATE:
				case self::WEIGHTED_PRESSURE_PLATE_LIGHT:
				case self::WEIGHTED_PRESSURE_PLATE_HEAVY:
					$backBlock = $this->level->getBlock($backPosition);
					if ($backBlock->isActive()) {
						return;
					}
					break;
				case self::REDSTONE_COMPARATOR_BLOCK:
					$comparator = $this->level->getBlock($backPosition);
					if ($comparator->isActive() && $this->getFace() == $comparator->getFace()) {
						return;
					}
					break;
				default:
					if (Block::$solid[$backBlockID]) {
						$solidBlock = $this->level->getBlock($backPosition);
						if ($solidBlock->getPoweredState() != Solid::POWERED_NONE) {
							return;
						}
					}
					break;
			}
			$result = $this->level->setBlock($this, Block::get(Block::REDSTONE_REPEATER_BLOCK, $this->meta), false, false);
			if ($result) {
				$delay = ($this->getDelay() + 1) * 2;
				$this->level->scheduleUpdate($this, $delay);
			}
		} else if ($type == Level::BLOCK_UPDATE_SCHEDULED) {
			$frontCoords = $this->getFrontBlockCoords();
			$frontBlock = $this->level->getBlock($frontCoords);
			if ($frontBlock !== null) {
				$frontBlock->onUpdate(Level::BLOCK_UPDATE_NORMAL);
			}
		}
	}

}

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

use pocketmine\block\redstoneBehavior\TransparentRedstoneComponent;
use pocketmine\block\Solid;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneWire extends TransparentRedstoneComponent {

	protected $id = self::REDSTONE_WIRE;

	public function __construct($meta = 0) {
		parent::__construct($this->id, $meta);
	}

	public function getHardness() {
		return 1;
	}

	public function getName() {
		return "Redstone Wire";
	}

	public function getDrops(Item $item) {
		return [
			[Item::REDSTONE_DUST, 0, 1],
		];
	}
	
	public function onUpdate($type) {
		switch ($type) {
			case Level::BLOCK_UPDATE_NORMAL:
			case Level::BLOCK_UPDATE_SCHEDULED:
				$this->updateNeighbors();
				$targetPower = $this->meta;
				$targetDirection = self::DIRECTION_NONE;
				foreach ($this->neighbors as $neighborDirection => $neighbor) {
					$neighborId = $neighbor->getId();
					switch ($neighborId) {
						case Block::REDSTONE_WIRE:
							$wirePower = $neighbor->getDamage();
							if ($wirePower > $targetPower) {
								$targetPower = $wirePower - 1;
								$targetDirection = $neighborDirection;
							}
							break;
						case Block::REDSTONE_TORCH_ACTIVE:
							$targetPower = self::REDSTONE_POWER_MAX;
							$targetDirection = $neighborDirection;
							break 2;
						default:
							if (Block::$solid[$neighborId] && $neighbor->getPoweredState() == Solid::POWERED_STRONGLY) {
								$targetPower = self::REDSTONE_POWER_MAX;
								$targetDirection = $neighborDirection;
								break 2;
							}
							if (Block::$transparent[$neighborId]) {
								$blockBelowId = $this->level->getBlockIdAt($neighbor->x, $neighbor->y - 1, $neighbor->z);
								if ($blockBelowId == Block::REDSTONE_WIRE) {
									$wirePower = $this->level->getBlockDataAt($neighbor->x, $neighbor->y - 1, $neighbor->z);;
									if ($wirePower > $targetPower) {
										$targetPower = $wirePower - 1;
										$targetDirection = $neighborDirection;
									}
								}
							}
							$blockAboveId = $this->level->getBlockIdAt($neighbor->x, $neighbor->y + 1, $neighbor->z);
							if ($blockAboveId == Block::REDSTONE_WIRE) {
								$wirePower = $this->level->getBlockDataAt($neighbor->x, $neighbor->y + 1, $neighbor->z);;
								if ($wirePower > $targetPower) {
									$targetPower = $wirePower - 1;
									$targetDirection = $neighborDirection;
								}
							}
							break;
					}
				}
				// check block below
				$blockBelowId = $this->level->getBlockIdAt($this->x, $this->y - 1, $this->z);
				if (Block::$solid[$blockBelowId]) {
					$blockBelow = $this->level->getBlock(new Vector3($this->x, $this->y - 1, $this->z));
					if ($blockBelow->getPoweredState() == Solid::POWERED_STRONGLY) {
						$targetPower = self::REDSTONE_POWER_MAX;
						$targetDirection = self::DIRECTION_SELF;
					}
				}
				if ($this->meta < $targetPower) {
					$this->meta = $targetPower;
					$this->level->setBlock($this, $this);
				} else {
					if ($this->meta == $targetPower && $targetDirection == self::DIRECTION_NONE) {
						$this->meta = self::REDSTONE_POWER_MIN;
						$this->level->setBlock($this, $this, false, false);
					}
				}
				$this->level->scheduleUpdate($this, 5);
				break;
		}
	}

	protected function isSuitableBlock($blockId, $direction) {
	}

	protected function updateNeighbors() {
		static $offsets = [
			self::DIRECTION_NORTH => [1, 0, 0],
			self::DIRECTION_SOUTH => [-1, 0, 0],
			self::DIRECTION_EAST => [0, 0, 1],
			self::DIRECTION_WEST => [0, 0, -1],
		];
		foreach ($offsets as $direction => $offset) {
			$this->neighbors[$direction] = $this->level->getBlock(new Vector3(
				$this->x + $offset[0], 
				$this->y + $offset[1], 
				$this->z + $offset[2]
			));
		}
	}

}

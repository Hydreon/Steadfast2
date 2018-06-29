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

use pocketmine\block\Solid;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class RedstoneWire extends Transparent {

	protected $id = self::REDSTONE_WIRE;
	/* @var $neighbors Block[] */
	protected $neighbors = [];

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
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		$this->collectNeighbors();
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
				case Block::REDSTONE_BLOCK:
					$targetPower = self::REDSTONE_POWER_MAX;
					$targetDirection = $neighborDirection;
					break 2;
				case Block::LEVER:
				case Block::WOODEN_BUTTON:
				case Block::STONE_BUTTON:
				case Block::WOODEN_PRESSURE_PLATE:
				case Block::STONE_PRESSURE_PLATE:
				case Block::WEIGHTED_PRESSURE_PLATE_LIGHT:
				case Block::WEIGHTED_PRESSURE_PLATE_HEAVY:
					if ($neighbor->isActive()) {
						$targetPower = self::REDSTONE_POWER_MAX;
						$targetDirection = $neighborDirection;
						break 2;
					}
					break;
				case Block::REDSTONE_REPEATER_BLOCK_ACTIVE:
					$frontBlockCoords = $neighbor->getFrontBlockCoords();
					if ($frontBlockCoords->x == $this->x && $frontBlockCoords->y == $this->y && $frontBlockCoords->z == $this->z) {
						$targetPower = self::REDSTONE_POWER_MAX;
						$targetDirection = $neighborDirection;
						break 2;
					}
					break;
				case Block::REDSTONE_COMPARATOR_BLOCK:
					if ($neighbor->isActive()) {
						$frontBlockCoords = $neighbor->getFrontBlockCoords();
						if ($frontBlockCoords->x == $this->x && $frontBlockCoords->y == $this->y && $frontBlockCoords->z == $this->z) {
							$targetPower = self::REDSTONE_POWER_MAX;
							$targetDirection = $neighborDirection;
							break 2;
						}
					}
					break;
				default:
					if (Block::$solid[$neighborId]) {
						if ($neighbor->getPoweredState() == Solid::POWERED_STRONGLY) {
							$targetPower = self::REDSTONE_POWER_MAX;
							$targetDirection = $neighborDirection;
							break 2;
						}
						if ($neighborDirection == self::DIRECTION_TOP || $neighborDirection == self::DIRECTION_BOTTOM) {
							break;
						}
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
		if ($targetDirection == self::DIRECTION_NONE && $this->meta != self::REDSTONE_POWER_MIN) { // lose charge
//			echo "X: " . $this->x . " Z: " . $this->z . " Redstone wire (loose charge) Old: " . $this->meta . " New: " . self::REDSTONE_POWER_MIN . PHP_EOL;
			$this->meta = self::REDSTONE_POWER_MIN;
			$this->level->setBlock($this, $this, true, true, $deep);
		} else if ($this->meta < $targetPower) { // found new power source
//			echo "X: " . $this->x . " Z: " . $this->z . " Redstone wire (set charge) Old: " . $this->meta . " New: " . $targetPower . PHP_EOL;
			$this->meta = $targetPower;
			$this->level->setBlock($this, $this, true, true, $deep);
		}
	}
	
	protected function collectNeighbors() {
		static $offsets = [
			self::DIRECTION_NORTH => [0, 0, -1],
			self::DIRECTION_SOUTH => [0, 0, 1],
			self::DIRECTION_EAST => [1, 0, 0],
			self::DIRECTION_WEST => [-1, 0, 0],
			self::DIRECTION_TOP => [0, 1, 0],
			self::DIRECTION_BOTTOM => [0, -1, 0],
		];
		foreach ($offsets as $direction => $offset) {
			switch ($direction) {
				case self::DIRECTION_TOP:
				case self::DIRECTION_BOTTOM:
					$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
					// why i put this code here? it's impossible
					if ($blockId == Block::WOODEN_BUTTON || $blockId == Block::STONE_BUTTON || Block::$solid[$blockId] || 
						$blockId == Block::REDSTONE_TORCH_ACTIVE || $blockId == Block::REDSTONE_TORCH) {
						
						$this->neighbors[$direction] = $this->level->getBlock(new Vector3(
							$this->x + $offset[0], 
							$this->y + $offset[1], 
							$this->z + $offset[2]
						));
					}
					break;
				default:
					$this->neighbors[$direction] = $this->level->getBlock(new Vector3(
						$this->x + $offset[0], 
						$this->y + $offset[1], 
						$this->z + $offset[2]
					));
					break;
			}
		}
	}

}

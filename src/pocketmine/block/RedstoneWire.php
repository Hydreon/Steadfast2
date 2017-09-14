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
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		$placeResult = parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
		if (!$placeResult) {
			return false;
		}
		$this->redstoneUpdate(self::REDSTONE_POWER_MIN, self::DIRECTION_SELF);
		return true;
	}
	
	public function onBreak(Item $item) {
		$result = parent::onBreak($item);
		if ($result) {
			$this->redstoneUpdate(self::REDSTONE_POWER_MIN, self::DIRECTION_SELF);
			return true;
		}
		return false;
	}

	protected function isSuitableBlock($blockId, $direction) {
	}

	public function redstoneUpdate($power, $fromDirection, $fromSolid = false) {
		$this->updateNeighbors();
		$power = max($power, self::REDSTONE_POWER_MIN);
		$oppositeFromDirection = $this->getOppositeDirection($fromDirection);
		$blockWasBroke = $this->id !== $this->level->getBlockIdAt($this->x, $this->y, $this->z);
		if (!$blockWasBroke) {
			// try to find neighbor with strongest charge
			$targetPower = $this->meta;
			$targetDirection = self::DIRECTION_SELF;
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
			
			// power in net decreasing
			if ($power == self::REDSTONE_POWER_MIN && $fromDirection !== self::DIRECTION_SELF) {
//				var_dump('заряд в сети падает');
				// found another power source in net
				if ($targetPower >= $this->meta && $targetDirection !== self::DIRECTION_SELF) {
//					var_dump('мы нашли источник заряда в сети');
//					var_dump('обновили зярад элемента на ' . $targetPower);
					$this->meta = $targetPower;
					$this->level->setBlock($this, $this);
					// send charge back
					if (isset($this->neighbors[$oppositeFromDirection]) && in_array($this->neighbors[$oppositeFromDirection]->getId(), self::REDSTONE_BLOCKS)) {
//						var_dump('отправили заряд назад');
						$this->neighbors[$oppositeFromDirection]->redstoneUpdate($this->meta - 1, $oppositeFromDirection);
					}
					return;
				} else {
					// remove power from element
//					var_dump('сняли заряд с элемента');
					$this->meta = self::REDSTONE_POWER_MIN;
					$this->level->setBlock($this, $this);
				}
			} else {
				// power in net is increasing
//				var_dump('заряд в сети растёт');
				if ($targetPower > $this->meta) {
//					var_dump('обновили зярад элемента на ' . $targetPower);
					$this->meta = $targetPower;
					$this->level->setBlock($this, $this);
					$power = $targetPower;
					$fromDirection = $this->getOppositeDirection($targetDirection);
				} else {
					return;
				}
			}
		}
		
		// prepare data
		$oppositeFromDirection = $this->getOppositeDirection($fromDirection);
		static $offsets = [
			self::DIRECTION_NORTH => [1, 0, 0],
			self::DIRECTION_SOUTH => [-1, 0, 0],
			self::DIRECTION_EAST => [0, 0, 1],
			self::DIRECTION_WEST => [0, 0, -1],
		];
		// neigbors logic
		$blockAboveWireId = $this->level->getBlockIdAt($this->x, $this->y + 1, $this->z);
		foreach ($this->neighbors as $direction => $neighbor) {
			if ($direction == $oppositeFromDirection) {
				continue;
			}
			$neighborId = $neighbor->getId(); 
			if (in_array($neighborId, self::REDSTONE_BLOCKS)) {
//				var_dump('Update redstone block attached to wire. Power: ' . ($power - 1) . ' Block: ' . get_class($neighbor));
				$neighbor->redstoneUpdate($power - 1, $direction, false);
			} else {
				if (Block::$solid[$neighborId]) {
					// update all neighbors except opposite direction
					$oppositeDirection = $this->getOppositeDirection($direction);
					foreach ($offsets as $offsetDrection => $offset) {
						if ($offsetDrection == $oppositeDirection) {
							continue;
						}
						$offsetBlockId = $this->level->getBlockIdAt($neighbor->x + $offset[0], $neighbor->y + $offset[1], $neighbor->z + $offset[2]);
						$isValidRedstoneComponent = $offsetBlockId != Block::REDSTONE_TORCH && 
							$offsetBlockId != Block::REDSTONE_TORCH_ACTIVE && 
							$offsetBlockId != Block::REDSTONE_WIRE;
						if ($isValidRedstoneComponent && in_array($offsetBlockId, self::REDSTONE_BLOCKS)) {
							$block = $this->level->getBlock(new Vector3(
								$neighbor->x + $offset[0], 
								$neighbor->y + $offset[1], 
								$neighbor->z + $offset[2]
							));
							$block->redstoneUpdate(self::REDSTONE_POWER_MAX, $offsetDrection, true);
						}
					}
				} else if (Block::$transparent[$neighborId]) {
					// try update block below
					$blockBelowId = $this->level->getBlockIdAt($neighbor->x, $neighbor->y - 1, $neighbor->z);
					$isValidRedstoneComponent = in_array($blockBelowId, self::REDSTONE_BLOCKS) &&
						$blockBelowId != Block::REDSTONE_TORCH && $blockBelowId != Block::REDSTONE_TORCH_ACTIVE;
					if ($isValidRedstoneComponent) {
						$blockBelow = $this->level->getBlock(new Vector3($neighbor->x, $neighbor->y - 1, $neighbor->z));
						$blockBelow->redstoneUpdate($power - 1, $direction, false);
					}
				}
				// try update block above
				if (Block::$transparent[$blockAboveWireId]) {
					$blockAboveId = $this->level->getBlockIdAt($neighbor->x, $neighbor->y + 1, $neighbor->z);
					$isValidRedstoneComponent = (Block::$transparent[$blockAboveId] && $blockAboveId == Block::REDSTONE_WIRE) ||
						(Block::$solid[$blockAboveId] && $blockAboveId != Block::REDSTONE_TORCH && $blockAboveId != Block::REDSTONE_TORCH_ACTIVE);
					if ($isValidRedstoneComponent) {
						$blockAbove = $this->level->getBlock(new Vector3($neighbor->x, $neighbor->y + 1, $neighbor->z));
						$blockAbove->redstoneUpdate($power - 1, $direction, false);
					}
				}
			}
		}
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

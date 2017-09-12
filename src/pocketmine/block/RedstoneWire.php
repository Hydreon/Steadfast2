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
		$this->updateNeighbors();
		$powerDirection = self::DIRECTION_SELF;
		$fromSolid = false;
		foreach ($this->neighbors as $direction => $neighbor) {
			if ($direction == self::DIRECTION_TOP) {
				continue;
			}
			$neighborId = $neighbor->getId();
			switch ($neighborId) {
				case Block::REDSTONE_WIRE:
					$neighborMeta = $neighbor->getDamage();
					if ($neighborMeta > $this->meta) {
						$this->meta = $neighborMeta;
						$powerDirection = $direction;
					}
					break;
				case Block::REDSTONE_TORCH_ACTIVE:
					$this->meta = self::REDSTONE_POWER_MAX;
					$powerDirection = $direction;
					break 2;
				default:
					if ($neighbor->isSolid() && $neighbor->getPoweredState() == Solid::POWERED_STRONGLY) {
						$this->meta = self::REDSTONE_POWER_MAX;
						$powerDirection = $direction;
						$fromSolid = true;
						break 2;
					}
					break;
			}
		}
		$placeResult = parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
		if (!$placeResult) {
			return false;
		}
		if ($this->meta > self::REDSTONE_POWER_MIN) {
			$this->redstoneUpdate($this->meta, $powerDirection, $fromSolid);
		}
		return true;
	}

	protected function isSuitableBlock($blockId, $direction) {
	}

	protected function redstoneUpdate($power, $fromDirection, $fromSolid = false) {
		if ($power <= self::REDSTONE_POWER_MIN && $fromDirection != self::DIRECTION_SELF) {
			return;
		}
		/** @todo написать логику самообновления */
		
		// neigbors logic
		static $offsets = [
			self::DIRECTION_NORTH => [1, 0, 0],
			self::DIRECTION_SOUTH => [-1, 0, 0],
			self::DIRECTION_EAST => [0, 0, 1],
			self::DIRECTION_WEST => [0, 0, -1],
		];
		$blockAboveWireId = $this->level->getBlockIdAt($this->x, $this->y + 1, $this->z);
		$this->updateNeighbors();
		foreach ($this->neighbors as $direction => $neighbor) {
			if ($direction == $fromDirection) {
				continue;
			}
			$neighborId = $neighbor->getId(); 
			if (in_array($neighborId, self::REDSTONE_BLOCKS)) {
				$neighbor->redstoneUpdate($power - 1, $direction, false);
			} else {
				if (isset(Block::$solid[$neighborId])) {
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
				} else if (isset(Block::$transparent[$neighborId])) {
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
				if (isset(Block::$transparent[$blockAboveWireId])) {
					$blockAboveId = $this->level->getBlockIdAt($neighbor->x, $neighbor->y + 1, $neighbor->z);
					$isValidRedstoneComponent = (isset(Block::$transparent[$neighborId]) && $blockAboveId == Block::REDSTONE_WIRE) ||
						(isset(Block::$solid[$neighborId]) && $blockAboveId != Block::REDSTONE_TORCH && $blockAboveId != Block::REDSTONE_TORCH_ACTIVE);
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

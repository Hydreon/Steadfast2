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
						break 2;
					}
					break;
			}
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}

	protected function isSuitableBlock($blockId, $direction) {
		switch ($direction) {
			case self::DIRECTION_NORTH:
			case self::DIRECTION_EAST:
			case self::DIRECTION_SOUTH:
			case self::DIRECTION_WEST:
				return in_array($blockId, self::REDSTONE_BLOCKS) || isset(Block::$solid[$blockId]);
			case self::DIRECTION_TOP:
				return isset(Block::$solid[$blockId]) || isset(Block::$transparent[$blockId]);
			default:
				return false;
		}
	}

	protected function redstoneUpdate($power, $fromDirection, $fromSolid = false) {
	}

	protected function updateNeighbors() {
		static $offsets = [
			self::DIRECTION_TOP => [0, 1, 0],
			self::DIRECTION_NORTH => [1, 0, 0],
			self::DIRECTION_SOUTH => [-1, 0, 0],
			self::DIRECTION_EAST => [0, 0, 1],
			self::DIRECTION_WEST => [0, 0, -1],
		];
		foreach ($offsets as $direction => $offset) {
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			if ($this->isSuitableBlock($blockId, $direction)) {
				$this->neighbors[$direction] = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
			}
		}
	}

}

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
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneRepeater extends Transparent {

	const FACE_NORTH = 0;
	const FACE_EAST = 1;
	const FACE_SOUTH = 2;
	const FACE_WEST = 3;
	const MAX_DELAY = 3;
	
	protected $id = self::REDSTONE_REPEATER_BLOCK;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function canBeFlowedInto() {
		return true;
	}

	public function canBeActivated() {
		return true;
	}

	public function getHardness() {
		return 1;
	}

	public function getResistance() {
		return 0;
	}

	public function getBoundingBox() {
		return null;
	}

	public function getDrops(Item $item) {
		return [
			[Item::REDSTONE_REPEATER, 0, 1],
		];
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		switch ($face) {
			case 1:
				if ($player->yaw <= 45 || $player->yaw >= 315) { // south
					$this->meta = self::FACE_SOUTH;
				} else if ($player->yaw >= 135 && $player->yaw <= 225) { // north
					$this->meta = self::FACE_NORTH;
				} else if ($player->yaw > 45 && $player->yaw < 135) { // west
					$this->meta = self::FACE_WEST;
				} else { // east
					$this->meta = self::FACE_EAST;
				}
				break;
			case 0:
			case 2:
			case 3:
			case 4:
			case 5:
			default:
				return false; // wrong face
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}
	
	public function getDelay() {
		return ($this->meta >> 2) & 0x0F;
	}
	
	public function onActivate(Item $item, Player $player = null) {
		$delay = $this->getDelay() + 1;
		if ($delay > self::MAX_DELAY) {
			$delay = 0;
		}
		$this->meta = ($this->meta & 0x03) | ($delay << 2);
		$this->level->setBlock($this, $this, true, true);
	}
	
	public function needScheduleOnUpdate() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		if ($type == Level::BLOCK_UPDATE_NORMAL) {
			$backPosition = $this->getBackBlockCoords();
			$backBlockID = $this->level->getBlockIdAt($backPosition->x, $backPosition->y, $backPosition->z);
			$isNeedSetBlock = false;
			switch ($backBlockID) {
				case self::REDSTONE_WIRE:
					$wire = $this->level->getBlock($backPosition);
					$isNeedSetBlock = $wire->meta > 0;
					break;
				case self::REDSTONE_TORCH_ACTIVE:
					$isNeedSetBlock = true;
					break;
				case self::WOODEN_BUTTON:
				case self::STONE_BUTTON:
				case self::LEVER:
				case self::WOODEN_PRESSURE_PLATE:
				case self::STONE_PRESSURE_PLATE:
				case self::WEIGHTED_PRESSURE_PLATE_LIGHT:
				case self::WEIGHTED_PRESSURE_PLATE_HEAVY:
					$backBlock = $this->level->getBlock($backPosition);
					$isNeedSetBlock = $backBlock->isActive();
					break;
				case self::REDSTONE_REPEATER_BLOCK_ACTIVE:
					$activeRepeater = $this->level->getBlock($backPosition);
					$isNeedSetBlock = ($this->getFace() == $activeRepeater->getFace());
					break;
				case self::REDSTONE_COMPARATOR_BLOCK:
					$comparator = $this->level->getBlock($backPosition);
					$isNeedSetBlock = $comparator->isActive() && $this->getFace() == $comparator->getFace();
					break;
				default:
					if (Block::$solid[$backBlockID]) {
						$solidBlock = $this->level->getBlock($backPosition);
						$isNeedSetBlock = $solidBlock->getPoweredState() != Solid::POWERED_NONE;
					}
					break;
			}
			if ($isNeedSetBlock) {
				$result = $this->level->setBlock($this, Block::get(Block::REDSTONE_REPEATER_BLOCK_ACTIVE, $this->meta), false, false, $deep);
				if ($result) {
					$delay = ($this->getDelay() + 1) * 2;
					$this->level->scheduleUpdate($this, $delay);
				}
			}
		} else if ($type == Level::BLOCK_UPDATE_SCHEDULED) {
			$frontCoords = $this->getFrontBlockCoords();
			$frontBlock = $this->level->getBlock($frontCoords);
			if ($frontBlock !== null) {
				$frontBlock->onUpdate(Level::BLOCK_UPDATE_NORMAL, $deep);
			}
		}
	}
	
	public function getBackBlockCoords() {
		$face = $this->meta & 0x03;
		switch ($face) {
			case self::FACE_NORTH:
				return new Vector3($this->x, $this->y, $this->z + 1);
			case self::FACE_EAST:
				return new Vector3($this->x - 1, $this->y, $this->z);
			case self::FACE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z - 1);
			case self::FACE_WEST:
				return new Vector3($this->x + 1, $this->y, $this->z);
		}
	}
	
	public function getFrontBlockCoords() {
		$face = $this->meta & 0x03;
		switch ($face) {
			case self::FACE_NORTH:
				return new Vector3($this->x, $this->y, $this->z - 1);
			case self::FACE_EAST:
				return new Vector3($this->x + 1, $this->y, $this->z);
			case self::FACE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z + 1);
			case self::FACE_WEST:
				return new Vector3($this->x - 1, $this->y, $this->z);
		}
	}
	
	public function getFace() {
		return $this->meta & 0x03;
	}

}

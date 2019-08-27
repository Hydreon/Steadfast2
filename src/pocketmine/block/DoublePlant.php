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

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\Player;

class DoublePlant extends Flowable {

	protected $id = self::DOUBLE_PLANT;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function canBeReplaced() {
		return true;
	}

	public function getName() {
		static $names = [
			0 => "Sunflower",
			1 => "Lilac",
			2 => "Double Tallgrass",
			3 => "Large Fern",
			4 => "Rose Bush",
			5 => "Peony"
		];
		return $names[$this->meta & 0x07];
	}


	public function onUpdate($type) {
		if ($type === Level::BLOCK_UPDATE_NORMAL) {
			$blockUnder = $this->getSide(0);
			if ($blockUnder->isTransparent() === true && $blockUnder->getId() != $this->id) { //Replace with common break method
				$this->getLevel()->setBlock($this, new Air(), false, false, true);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return false;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		if ($this->getDamage() < 0x08) {
			$down = $this->getSide(0);
			if ($down->getId() === self::GRASS) {
				$blockAbove = $this->level->getBlockIdAt($this->x, $this->y + 1, $this->z);
				if ($blockAbove == Block::AIR) {
					if ($this->level->setBlock($this, $this, true, true)) {
						$upperPart = clone $this;
						$upperPart->y++;
						$upperPart->setDamage($this->getDamage() | 0x08);
						if ($this->level->setBlock($upperPart, $upperPart, true, true)) {
							return true;
						}
						$this->level->setBlock($this, Block::get(Block::AIR), true, true);
					}
				}
			}
		}
		return false;
	}

	public function onBreak(Item $item){
		if (!$this->getLevel()->setBlock($this, new Air(), true, true)) {
			return false;
		}
		$meta = $this->getDamage();
		if ($meta < 0x08 && $this->level->getBlockIdAt($this->x, $this->y + 1, $this->z) == $this->id) {
			$this->y++;
			if ($this->level->setBlock($this, Block::get(Block::AIR), true, true)) {
				return true;
			}
			$this->y--;
			$this->level->setBlock($this, $this, true, true);
			return false;
		} else if ($meta >= 0x08 && $this->level->getBlockIdAt($this->x, $this->y - 1, $this->z) == $this->id) {
			$this->y--;
			if ($this->level->setBlock($this, Block::get(Block::AIR), true, true)) {
				return true;
			}
			$this->y++;
			$this->level->setBlock($this, $this, true, true);
			return false;
		}
		return true;
	}
	
	public function getBreakTime(Item $item) {
		return 0.05;
	}

	public function getDrops(Item $item) {
		if ($this->meta >= 0x08 && $this->level->getBlockIdAt($this->x, $this->y - 1, $this->z) == $this->id) {
			$meta = $this->level->getBlockDataAt($this->x, $this->y - 1, $this->z);
		} else {
			$meta = $this->meta;
		}
		return [
			[$this->id, $meta & 0x07, 1]
		];
	}

}
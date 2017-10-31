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
use pocketmine\item\Item;
use pocketmine\Player;

class RedstoneRepeater extends Transparent {

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
					$this->meta = 2;
				} else if ($player->yaw >= 135 && $player->yaw <= 225) { // north
					$this->meta = 0;
				} else if ($player->yaw > 45 && $player->yaw < 135) { // west
					$this->meta = 3;
				} else { // east
					$this->meta = 1;
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

}

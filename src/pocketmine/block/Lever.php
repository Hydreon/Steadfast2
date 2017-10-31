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
use pocketmine\item\Tool;
use pocketmine\Player;

class Lever extends Transparent{

	protected $id = self::LEVER;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Lever";
	}

	public function canBeActivated(){
		return true;
	}

	public function getHardness(){
		return 0.5;
	}

	public function getToolType(){
		return Tool::TYPE_NONE;
	}

	public function getDrops(Item $item){
		return [
			[Item::LEVER, 0, 1],
		];
	}
	
	public function canBeFlowedInto(){
		return true;
	}
	
	public function getResistance(){
		return 0;
	}

	public function getBoundingBox(){
		return null;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {	
		switch ($face) {
			case 0:
				if (($player->yaw > 45 && $player->yaw < 135) || ($player->yaw > 225 && $player->yaw < 315)) {
					$this->meta = 7;
				} else {
					$this->meta = 0;
				}
				break;
			case 1:
				if (($player->yaw > 45 && $player->yaw < 135) || ($player->yaw > 225 && $player->yaw < 315)) {
					$this->meta = 6;
				} else {
					$this->meta = 5;
				}
				break;
			case 2:
				$this->meta = 4;
				break;
			case 3:
				$this->meta = 3;
				break;
			case 4:
				$this->meta = 2;
				break;
			case 5:
				$this->meta = 1;
				break;
			default:
				return false; // wrong face
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}
	
	public function isActive() {
		return ($this->meta >> 3) & 0x01;
	}
	
	public function onActivate(Item $item, Player $player = null) {
		$this->toggle();
	}
	
	public function toggle() {
		if ($this->isActive()) {
			$this->meta -= 8;
		} else {
			$this->meta += 8;
		}
		$this->level->setBlock($this, $this, true, true);
	}
	
	public function getFace() {
		$faceData = $this->meta & 0x07;
		switch ($faceData) {
			case 0:
			case 7:
				return self::FACE_DOWN;
			case 1:
				return self::FACE_EAST;
			case 2:
				return self::FACE_WEST;
			case 3:
				return self::FACE_SOUTH;
			case 4:
				return self::FACE_NORTH;
			case 5:
			case 6:
				return self::FACE_UP;
		}
	}
}
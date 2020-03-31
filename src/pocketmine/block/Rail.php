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
use pocketmine\block\Transparent;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Rail extends Transparent{
	
	const META_NORTH_SOUTH = 0;
	const META_EAST_WEST = 1;
	const META_EAST_ASC = 2;
	const META_WEST_ASC = 3;
	const META_NORTH_ASC = 4;
	const META_SOUTH_ASC = 5;
	const META_SOUTH_EAST = 6;
	const META_SOUTH_WEST = 7;
	const META_NORTH_WEST = 8;
	const META_NORTH_EAST = 9;

	private static $suitableMeta = [
		"s" => [
			self::META_NORTH_SOUTH,
			self::META_SOUTH_ASC,
			self::META_NORTH_EAST,
			self::META_NORTH_WEST,
		],
		"n" => [
			self::META_NORTH_SOUTH,
			self::META_NORTH_ASC,
			self::META_SOUTH_EAST,
			self::META_SOUTH_WEST,
		],
		"e" => [
			self::META_EAST_WEST,
			self::META_EAST_ASC,
			self::META_NORTH_WEST,
			self::META_SOUTH_WEST,
		],
		"w" => [
			self::META_EAST_WEST,
			self::META_WEST_ASC,
			self::META_NORTH_EAST,
			self::META_SOUTH_EAST,
		],
	];
	
	protected $id = self::RAIL;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	public function getName(){
		return "Rail";
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getHardness(){
		return 3;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		if (!($target instanceof Solid)) {
			return false;
		}
//		$this->meta = 0;
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}
	
	private function isFreeForConnection($x, $y, $z) {
		// south +z
		// north -z
		// east +x
		// west -x
		$level = $this->level;
		$meta = $level->getBlockDataAt($x, $y, $z);
		switch ($meta) {
			case self::META_NORTH_SOUTH:
				return $level->getBlockIdAt($x, $y, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z - 1), self::$suitableMeta["n"]) || 
					$level->getBlockIdAt($x, $y, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z + 1), self::$suitableMeta["s"]);
			case self::META_EAST_WEST:
				return $level->getBlockIdAt($x - 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y, $z), self::$suitableMeta["w"]) || 
					$level->getBlockIdAt($x + 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x + 1, $y, $z), self::$suitableMeta["e"]);
			case self::META_EAST_ASC:
				return $level->getBlockIdAt($x + 1, $y + 1, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x + 1, $y + 1, $z), self::$suitableMeta["e"]) || 
					(($level->getBlockIdAt($x - 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y, $z), self::$suitableMeta["w"])) && 
					($level->getBlockIdAt($x - 1, $y - 1, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y - 1, $z), self::$suitableMeta["w"])));
			case self::META_WEST_ASC:
				return $level->getBlockIdAt($x - 1, $y + 1, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y + 1, $z), self::$suitableMeta["w"]) ||
					(($level->getBlockIdAt($x + 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x + 1, $y, $z), self::$suitableMeta["e"])) && 
					($level->getBlockIdAt($x + 1, $y - 1, $z) != $this->id) || 
					!in_array($level->getBlockDataAt($x + 1, $y - 1, $z), self::$suitableMeta["e"]));
			case self::META_NORTH_ASC:
				return $level->getBlockIdAt($x, $y + 1, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y + 1, $z - 1), self::$suitableMeta["n"]) ||
					(($level->getBlockIdAt($x, $y, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z + 1), self::$suitableMeta["s"])) && 
					($level->getBlockIdAt($x, $y - 1, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y - 1, $z + 1), self::$suitableMeta["s"])));
			case self::META_SOUTH_ASC:
				return $level->getBlockIdAt($x, $y + 1, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y + 1, $z + 1), self::$suitableMeta["s"]) || 
					(($level->getBlockIdAt($x, $y, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z - 1), self::$suitableMeta["n"])) && 
					($level->getBlockIdAt($x, $y - 1, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y - 1, $z - 1), self::$suitableMeta["n"])));
			case self::META_SOUTH_EAST:
				return $level->getBlockIdAt($x, $y, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z + 1), self::$suitableMeta["s"]) || 
					$level->getBlockIdAt($x + 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x + 1, $y, $z), self::$suitableMeta["e"]);
			case self::META_SOUTH_WEST:
				return $level->getBlockIdAt($x, $y, $z + 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z + 1), self::$suitableMeta["s"]) || 
					$level->getBlockIdAt($x - 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y, $z), self::$suitableMeta["w"]);
			case self::META_NORTH_WEST:
				return $level->getBlockIdAt($x, $y, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z - 1), self::$suitableMeta["n"]) || 
					$level->getBlockIdAt($x - 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x - 1, $y, $z), self::$suitableMeta["w"]);
			case self::META_NORTH_EAST:
				return $level->getBlockIdAt($x, $y, $z - 1) != $this->id || 
					!in_array($level->getBlockDataAt($x, $y, $z - 1), self::$suitableMeta["n"]) || 
					$level->getBlockIdAt($x + 1, $y, $z) != $this->id || 
					!in_array($level->getBlockDataAt($x + 1, $y, $z), self::$suitableMeta["e"]);
		}
	}
	
	private function getOffsetFoRailByDirection($direction) {
		// south +z
		// north -z
		// east +x
		// west -x
		switch ($direction) {
			case "north":
				$offset = [0, 0, -1];
				break;
			case "south":
				$offset = [0, 0, 1];
				break;
			case "west":
				$offset = [-1, 0, 0];
				break;
			case "east":
				$offset = [1, 0, 0];
				break;
			default:
				return null;
		}
		if ($this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]) == $this->id) {
			return $offset;
		} else if ($this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1] + 1, $this->z + $offset[2]) == $this->id) {
			$offset[1] = 1;
			return $offset;
		} else if ($this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1] - 1, $this->z + $offset[2]) == $this->id) {
			$offset[1] = -1;
			return $offset;
		}
		return null;
	}
	
	public function onUpdate($type) {
		// south +z
		// north -z
		// east +x
		// west -x
		static $directions = [ "s" => "south", "e" => "east", "n" => "north", "w" => "west" ];
		$oneEnd = "";
		$oneEndOffset = null;
		$anotherEnd = "";
		$anotherEndOffset = null;
		foreach ($directions as $direction => $directionFullName) {
			$blockOffset = $this->getOffsetFoRailByDirection($directionFullName);
			if (is_null($blockOffset)) {
				continue;
			}
			if ($this->isFreeForConnection($this->x + $blockOffset[0], $this->y + $blockOffset[1], $this->z + $blockOffset[2]) && empty($oneEnd)) {
				$oneEndOffset = $blockOffset;
				$oneEnd = $direction;
			} else {
				if ($this->isFreeForConnection($this->x + $blockOffset[0], $this->y + $blockOffset[1], $this->z + $blockOffset[2]) || 
					in_array($this->level->getBlockDataAt($this->x + $blockOffset[0], $this->y + $blockOffset[1], $this->z + $blockOffset[2]), self::$suitableMeta[$direction])) {
					
					if (empty($anotherEnd)) {
						$anotherEndOffset = $blockOffset;
						$anotherEnd = $direction;
					} else {
						return;
					}
				}
			}
		}
		if (empty($oneEnd)) {
			return;
		}
		$oldMeta = $this->meta;
		switch ($oneEnd) {
			case "s":
				switch ($anotherEnd) {
					case "e":
						$this->meta = self::META_SOUTH_EAST;
						break;
					case "n":
						if ($oneEndOffset[1] == $anotherEndOffset[1] || $oneEndOffset[1] + $anotherEndOffset[1] == -1) {
							$this->meta = self::META_NORTH_SOUTH;
						} else if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_SOUTH_ASC;
						} else if ($anotherEndOffset[1] == 1) {
							$this->meta = self::META_NORTH_ASC;
						}
						break;
					case "w":
						$this->meta = self::META_SOUTH_WEST;
						break;
					default:
						if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_SOUTH_ASC;
						} else {
							$this->meta = self::META_NORTH_SOUTH;
						}
						break;
				}
				break;
			case "e":
				switch ($anotherEnd) {
					case "s":
						$this->meta = self::META_SOUTH_EAST;
						break;
					case "n":
						$this->meta = self::META_NORTH_EAST;
						break;
					case "w":
						if ($oneEndOffset[1] == $anotherEndOffset[1] || $oneEndOffset[1] + $anotherEndOffset[1] == -1) {
							$this->meta = self::META_EAST_WEST;
						} else if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_EAST_ASC;
						} else if ($anotherEndOffset[1] == 1) {
							$this->meta = self::META_WEST_ASC;
						}
						break;
					default:
						if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_EAST_ASC;
						} else {
							$this->meta = self::META_EAST_WEST;
						}
						break;
				}
				break;
			case "n":
				switch ($anotherEnd) {
					case "e":
						$this->meta = self::META_NORTH_EAST;
						break;
					case "s":
						if ($oneEndOffset[1] == $anotherEndOffset[1] || $oneEndOffset[1] + $anotherEndOffset[1] == -1) {
							$this->meta = self::META_NORTH_SOUTH;
						} else if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_NORTH_ASC;
						} else if ($anotherEndOffset[1] == 1) {
							$this->meta = self::META_SOUTH_ASC;
						}
						break;
					case "w":
						$this->meta = self::META_NORTH_WEST;
						break;
					default:
						if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_NORTH_ASC;
						} else {
							$this->meta = self::META_NORTH_SOUTH;
						}
						break;
				}
				break;
			case "w":
				switch ($anotherEnd) {
					case "e":
						if ($oneEndOffset[1] == $anotherEndOffset[1] || $oneEndOffset[1] + $anotherEndOffset[1] == -1) {
							$this->meta = self::META_EAST_WEST;
						} else if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_WEST_ASC;
						} else if ($anotherEndOffset[1] == 1) {
							$this->meta = self::META_EAST_ASC;
						}
						break;
					case "n":
						$this->meta = self::META_NORTH_WEST;
						break;
					case "s":
						$this->meta = self::META_SOUTH_WEST;
						break;
					default:
						if ($oneEndOffset[1] == 1) {
							$this->meta = self::META_WEST_ASC;
						} else {
							$this->meta = self::META_EAST_WEST;
						}
						break;
				}
				break;
		}
		
		if ($this->meta != $oldMeta) {
			$this->level->setBlock($this, $this, false, false);
		}
		if (!empty($oneEnd) && $oneEndOffset[1] == -1) {
			$block = $this->level->getBlock(new Vector3($this->x + $oneEndOffset[0], $this->y + $oneEndOffset[1], $this->z + $oneEndOffset[2]));
			switch ($oneEnd) {
				case "s":
					$block->meta = self::META_SOUTH_ASC;
					break;
				case "n":
					$block->meta = self::META_NORTH_ASC;
					break;
				case "e":
					$block->meta = self::META_WEST_ASC;
					break;
				case "w":
					$block->meta = self::META_EAST_ASC;
					break;
			}
			$this->level->setBlock($block, $block, false, false);
		}
		if (!empty($anotherEnd) && $anotherEndOffset[1] == -1) {
			$block = $this->level->getBlock(new Vector3($this->x + $anotherEndOffset[0], $this->y + $anotherEndOffset[1], $this->z + $anotherEndOffset[2]));
			switch ($oneEnd) {
				case "s":
					$block->meta = self::META_SOUTH_ASC;
					break;
				case "n":
					$block->meta = self::META_NORTH_ASC;
					break;
				case "e":
					$block->meta = self::META_WEST_ASC;
					break;
				case "w":
					$block->meta = self::META_EAST_ASC;
					break;
			}
			$this->level->setBlock($block, $block, false, false);
		}
	}
}
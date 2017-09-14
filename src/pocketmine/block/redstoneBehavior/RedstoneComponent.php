<?php

namespace pocketmine\block\redstoneBehavior;

use pocketmine\block\Block;

abstract class RedstoneComponent extends Block {
	
	const REDSTONE_BLOCKS = [
		self::REDSTONE_WIRE,
		self::REDSTONE_TORCH,
		self::REDSTONE_TORCH_ACTIVE,
	];
	
	const REDSTONE_POWER_MIN = 0;
	const REDSTONE_POWER_MAX = 15;
	
	const DIRECTION_TOP = 5;
	const DIRECTION_NORTH = 1;
	const DIRECTION_EAST = 3;
	const DIRECTION_SOUTH = 2;
	const DIRECTION_WEST = 4;
	const DIRECTION_BOTTOM = 0;
	const DIRECTION_SELF = 6;

	/* @var $neighbors Block[] */
	protected $neighbors = [];
	
	function getOppositeDirection($direction) {
		switch ($direction) {
			case self::DIRECTION_BOTTOM:
				return self::DIRECTION_TOP;
			case self::DIRECTION_TOP:
				return self::DIRECTION_BOTTOM;
			case self::DIRECTION_NORTH;
				return self::DIRECTION_SOUTH;
			case self::DIRECTION_SOUTH;
				return self::DIRECTION_NORTH;
			case self::DIRECTION_EAST;
				return self::DIRECTION_WEST;
			case self::DIRECTION_WEST;
				return self::DIRECTION_EAST;
		}
		return -1;
	}

	/**
	 * 
	 * @param integer $blockId
	 * @param integer $direction
	 * @return boolean
	 */
	abstract protected function isSuitableBlock($blockId, $direction);
	
	abstract protected function updateNeighbors();
	
	abstract public function redstoneUpdate($power, $fromDirection, $fromSolid = false);
	
}

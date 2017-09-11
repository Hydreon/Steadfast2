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

	protected $neighbors = [];
	
	/**
	 * 
	 * @param integer $blockId
	 * @param integer $direction
	 * @return boolean
	 */
	abstract protected function isSuitableBlock($blockId, $direction);
	
	abstract protected function updateNeighbors();
	
	abstract protected function redstoneUpdate($power, $fromDirection, $fromSolid = false);
	
}

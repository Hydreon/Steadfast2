<?php

namespace pocketmine\block;

use pocketmine\block\redstoneBehavior\FlowableRedstoneComponent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneTorch extends FlowableRedstoneComponent {

	protected $id = self::REDSTONE_TORCH;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function getName() {
		return "Redstone Torch";
	}

	public function getLightLevel() {
		return 0;
	}

	public function getDrops(Item $item) {
		return [
			[self::REDSTONE_TORCH_ACTIVE, 0, 1],
		];
	}

	public function onUpdate($type) {
		static $faces = [
			1 => 4,
			2 => 5,
			3 => 2,
			4 => 3,
			5 => 0,
			6 => 0,
			0 => 0,
		];
		
		if ($type === Level::BLOCK_UPDATE_NORMAL) {
			$below = $this->getSide(0);
			$side = $this->getDamage();
			if ($this->getSide($faces[$side])->isTransparent() === true && !($side == 0 && ($below->getId() === self::FENCE || $below->getId() === self::COBBLE_WALL))) {
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
			}
		}
		return false;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		static $faces = [
			1 => 5,
			2 => 4,
			3 => 3,
			4 => 2,
			5 => 1,
		];
		if ($target->isTransparent() === false && $face !== 0) {
			$this->meta = $faces[$face];
			$this->getLevel()->setBlock($block, $this, true, true);
			return true;
		} else {
			$below = $this->getSide(0);
			if ($below->isTransparent() === false || $below->getId() === self::FENCE || $below->getId() === self::COBBLE_WALL) {
				$this->meta = 0;
				$this->getLevel()->setBlock($block, $this, true, true);
				return true;
			}
		}
		return false;
	}
	
	protected function isSuitableBlock($blockId, $direction) {
		static $unsuitableRedstoneBlocks = [
			self::REDSTONE_TORCH,
			self::REDSTONE_TORCH_ACTIVE,
		];
		switch ($direction) {
			case self::DIRECTION_NORTH:
				return self::$solid[$blockId];
			case self::DIRECTION_NORTH:
			case self::DIRECTION_EAST:
			case self::DIRECTION_SOUTH:
			case self::DIRECTION_WEST:
				return in_array($blockId, self::REDSTONE_BLOCKS) && !in_array($blockId, $unsuitableRedstoneBlocks);
			default:
				return false;
		}
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
			$topBlockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			if ($this->isSuitableBlock($topBlockId, $direction)) {
				$this->neighbors[$direction] = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
			}
		}
	}
	
	protected function redstoneUpdate($power = 0) {
		
	}

}

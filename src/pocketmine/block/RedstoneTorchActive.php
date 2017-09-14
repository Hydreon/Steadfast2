<?php

namespace pocketmine\block;

use pocketmine\block\redstoneBehavior\FlowableRedstoneComponent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneTorchActive extends FlowableRedstoneComponent {
	
	protected $id = self::REDSTONE_TORCH_ACTIVE;
	
	public function __construct($meta = 0){
		$this->meta = $meta;
	}
	
	public function getName(){
		return "Glowing Redstone Torch";
	}
	
	public function getLightLevel() {
		return 7;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		static $faces = [
			1 => 5,
			2 => 4,
			3 => 3,
			4 => 2,
			5 => 1,
		];
		
		// The torch can't be put on the bottom of block
		if ($face == 0) {
			return false;
		}
		if ($target->isSolid()) {
			if ($target->getPoweredState() == Solid::POWERED_NONE) {
				$this->meta = $faces[$face];
				$this->level->setBlock($block, $this, true, true);
				$this->redstoneUpdate(self::REDSTONE_POWER_MAX, self::DIRECTION_SELF);
			} else {
				$torch = Block::get(Block::REDSTONE_TORCH, $faces[$face]);
				$this->getLevel()->setBlock($block, $torch, true, true);
			}
			return true;
		} else if ($face == 1) { // place on top
			// upside-down stairs
			if ($target instanceof Stair && $target->isUpsideDown()) {
				$this->meta = $faces[$face];
				$this->getLevel()->setBlock($block, $this, true, true);
				return true;
			}
			if ($target instanceof Glass) {
				$this->meta = $faces[$face];
				$this->getLevel()->setBlock($block, $this, true, true);
				return true;
			}
			/** @todo Slabs */
			/** @todo Fence */
			/** @todo Wall */
			/** @todo Hopper */
			/** @todo Snow */
			/** @todo SoulSand */
			/** @todo Piston */
			/** @todo MonsterSpawner */
		}
		return false;
	}
	
	public function onUpdate($type) {
		if ($this->meta == 0) {
			return Level::BLOCK_UPDATE_NORMAL;
		}
		
		static $faces = [
			1 => 4,
			2 => 5,
			3 => 2,
			4 => 3,
			5 => 0,
		];
		
		if ($this->meta == 5) { // placed on top of block
			$bottomBlock = $this->getSide(Vector3::SIDE_DOWN);
			if ($bottomBlock instanceof Air) {
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;				
			}
		} else {
			$block = $this->getSide($faces[$this->meta]);
			if ($block->isTransparent()) {
				$this->getLevel()->useBreakOn($this);
				return Level::BLOCK_UPDATE_NORMAL;
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
			case self::DIRECTION_TOP:
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
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			if ($this->isSuitableBlock($blockId, $direction)) {
				$this->neighbors[$direction] = $this->level->getBlock(new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]));
			}
		}
	}
	
	public function onBreak(Item $item) {
		$result = parent::onBreak($item);
		if ($result) {
			$this->redstoneUpdate(self::REDSTONE_POWER_MIN, self::DIRECTION_SELF);
			return true;
		}
		return false;
	}
	
	public function redstoneUpdate($power, $fromDirection, $fromSolid = false) {
		if (!$fromSolid && $fromDirection != self::DIRECTION_SELF) {
			return;
		}
		$this->updateNeighbors();
		if ($fromDirection == $this->meta && $power > self::REDSTONE_POWER_MIN) { // power from attached block
			$unlitTorch = Block::get(Block::REDSTONE_TORCH, $this->meta);
			$this->level->setBlock($this, $unlitTorch, true, true);
			$power = self::REDSTONE_POWER_MIN;
		}
				
		foreach ($this->neighbors as $neighborDirection => $neighbor) {
			if (in_array($neighbor->getId(), self::REDSTONE_BLOCKS)) {
				$neighbor->redstoneUpdate($power, $neighborDirection);
			} else if ($neighbor->isSolid()) {
				// if top neighbor is solid it become strongly charged 
				// and pass charge in another 5 directions
				static $offsets = [
					self::DIRECTION_TOP => [0, 1, 0],
					self::DIRECTION_NORTH => [1, 0, 0],
					self::DIRECTION_SOUTH => [-1, 0, 0],
					self::DIRECTION_EAST => [0, 0, 1],
					self::DIRECTION_WEST => [0, 0, -1],
				];
				foreach ($offsets as $direction => $offset) {
					$blockId = $this->level->getBlockIdAt($neighbor->x + $offset[0], $neighbor->y + $offset[1], $neighbor->z + $offset[2]);
					if (!in_array($blockId, self::REDSTONE_BLOCKS)) {
						continue;
					}		
					$rsComponent = $this->level->getBlock(new Vector3($neighbor->x + $offset[0], $neighbor->y + $offset[1], $neighbor->z + $offset[2]));
					$rsComponent->redstoneUpdate($power, $direction, true);
				}
			}
		}
	}
	
}
<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneTorchActive extends Flowable {
	
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
	
	public function getDrops(Item $item) {
		return [
			[self::REDSTONE_TORCH_ACTIVE, 0, 1],
		];
	}
	
	public function isMayBeDestroyedByPiston() {
		return true;
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
	
	public function needScheduleOnUpdate() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		static $faces = [
			1 => 4,
			2 => 5,
			3 => 2,
			4 => 3,
			5 => 0,
		];
		
		if ($this->meta == 5) { // placed on top of block
			$block = $this->getSide(Vector3::SIDE_DOWN);
			if ($block instanceof Air) {
				$this->getLevel()->useBreakOn($this);
				return;
			}
		} else {
			$block = $this->getSide($faces[$this->meta]);
			if ($block->isTransparent()) {
				$this->getLevel()->useBreakOn($this);
				return;
			}
		}
		if ($block->isSolid() && $block->getPoweredState() !== Solid::POWERED_NONE) {
//			echo "X: " . $this->x . " Z: " . $this->z . " Update active torch" . PHP_EOL;
			$unlitTorch = Block::get(Block::REDSTONE_TORCH, $this->meta);
			$this->level->setBlock($this, $unlitTorch, false, true, $deep);
		}			
	}
	
}
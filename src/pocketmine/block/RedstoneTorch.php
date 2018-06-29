<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\math\Vector3;

class RedstoneTorch extends RedstoneTorchActive {

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
		if ($block->isSolid() && $block->getPoweredState() == Solid::POWERED_NONE) {
//			echo "X: " . $this->x . " Z: " . $this->z . " Update torch" . PHP_EOL;
			$litTorch = Block::get(Block::REDSTONE_TORCH_ACTIVE, $this->meta);
			$this->level->setBlock($this, $litTorch, false, true, $deep);
		}			
	}

}

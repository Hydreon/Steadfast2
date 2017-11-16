<?php

namespace pocketmine\block;

class StickyPiston extends Piston {
	
	protected $id = self::STICKY_PISTON;
	
	public function __construct($meta = 0) {
		parent::__construct($meta);
	}
	
	protected function retract($tile, $extendSide) {
//		echo "X: " . $this->x . " Z: " . $this->z . " Retract sticky piston" . PHP_EOL;
		$tile->namedtag['Progress'] = 0;
		$tile->namedtag['State'] = 0;
//		$tile->namedtag['HaveCharge'] = 0;
		$extendBlock = $this->getSide($extendSide);
		$movingBlock = $extendBlock->getSide($extendSide);
//		echo $extendBlock . PHP_EOL;
		if ($movingBlock instanceof Solid) {
			$this->getLevel()->setBlock($movingBlock, Block::get(self::AIR), true, true);
			$this->getLevel()->setBlock($extendBlock, $movingBlock, true, true);
		} else {
			$this->getLevel()->setBlock($extendBlock, Block::get(self::AIR), true, true);
		}
//		var_dump("Piston remove charge 3 " . $this->x . " " . $this->z);
		$tile->spawnToAll();
	}
}

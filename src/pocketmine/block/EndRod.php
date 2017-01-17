<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\Player;

class EndRod extends Transparent {
    
    const FACING_DOWN = 0;
    const FACING_UP = 1;
    const FACING_NORTH = 2;
    const FACING_SOUTH = 3;
    const FACING_WEST = 4;
    const FACING_EAST = 5;
    
    protected $id = self::END_ROD;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'End Rod';
    }
    
    public function getLightLevel() {
        return 14;
    }
    
    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
        if ($target->isTransparent()) {
            return false;
        }
        if ($face < 2) {
            $this->meta = $face;
        } else {
            $this->meta = $face + (($face % 2 == 0) ? 1 : -1);
        }
        $this->getLevel()->setBlock($block, $this, true, true);
        return true;
    }
    
    public function getDrops(Item $item) {
        return [
            [ $this->id, 0, 1 ]
        ];
    }
    
}

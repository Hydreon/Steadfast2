<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class EndPortal extends Solid {
    
    protected $id = self::END_PORTAL;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'End Portal';
    }
    
    public function isBreakable(Item $item) {
        return false;
    }
    
    public function getDrops(Item $item) {
        return [];
    }
    
    public function getLightLevel() {
        return 15;
    }
    
}

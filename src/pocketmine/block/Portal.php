<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class Portal extends Transparent {
    
    protected $id = self::PORTAL;
	
	public function __construct() {
		
	}
    
    public function getName() {
        return 'Portal';
    }
    
    public function isBreakable(Item $item) {
        return false;
    }
    
    public function getDrops(Item $item) {
        return [];
    }
    
}

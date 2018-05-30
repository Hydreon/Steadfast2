<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class EndGateway extends Transparent {
    
    protected $id = self::END_GATEWAY;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function isBreakable(Item $item) {
        return false;
    }
    
    public function getName() {
        return 'End Gateway';
    }
    
    public function getLightLevel(){
		return 15;
	}
    
    public function getDrops(Item $item) {
        return [];
    }
    
}

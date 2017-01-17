<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class StainedGlass extends Solid {
    
    protected $id = self::STAINED_GLASS;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return $this->getColorName() . 'Stained Glass';
    }
    
    public function getHardness() {
        return 0.3;
    }
    
    public function getDrops(Item $item) {
        return [];
    }
    
    protected function getColorName() {
        /** @todo get color name based on meta */
        return '';
    }
    
}

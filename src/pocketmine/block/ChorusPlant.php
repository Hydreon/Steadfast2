<?php

namespace pocketmine\block;

use pocketmine\item\Tool;
use pocketmine\item\Item;

class ChorusPlant extends Solid {
    
    protected $id = self::CHORUS_PLANT;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName(){
		return "Chrorus Plant";
	}
    
    public function getHardness() {
        return 0.4;
    }
    
    public function getToolType() {
        return Tool::TYPE_AXE;
    }
    
    public function getDrops(Item $item) {
        return [
            [ Item::CHORUS_FRUIT, 0, 2 ]
        ];
    }
}

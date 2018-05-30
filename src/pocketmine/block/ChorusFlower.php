<?php

namespace pocketmine\block;

use pocketmine\item\Tool;

class ChorusFlower extends Solid {
    
    protected $id = self::CHORUS_FLOWER;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName(){
		return "Chrorus Flower";
	}
    
    public function getToolType() {
        return Tool::TYPE_AXE;
    }
    
    public function getHardness(){
		return 0.4;
	}
    
}

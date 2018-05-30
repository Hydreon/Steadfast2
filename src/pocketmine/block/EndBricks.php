<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class EndBricks extends Solid {
    
    protected  $id = self::END_BRICKS;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'End Stone Bricks';
    }
    
    public function getHardness() {
        return 0.8;
    }
    
    public function getToolType() {
        return Tool::TYPE_PICKAXE;
    }
    
    public function getDrops(Item $item) {
        if ($item->isPickaxe()) {
            return [
                [$this->id, $this->meta, 1]
            ];
        }
        return [];
    }
    
}

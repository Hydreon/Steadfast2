<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class PurpurStairs extends Stair {

    protected $id = self::PURPUR_STAIRS;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'Purpur Stairs';
    }
    
    public function getHardness() {
        return 1.5;
    }
    
    public function getToolType() {
        return Tool::TYPE_PICKAXE;
    }
    
    public function getDrops(Item $item) {
        if ($item->isPickaxe()) {
            return [
                [ $this->id, $this->meta, 1 ]
            ];
        }
        return [];
    }
    
}

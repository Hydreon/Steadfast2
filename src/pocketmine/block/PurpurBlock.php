<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class PurpurBlock extends Solid {
    
    const META_TYPE_PILLAR = 2;

    protected $id = self::PURPUR_BLOCK;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'Purpur Block';
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

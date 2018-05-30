<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class RedstoneLamp extends Solid {
    
    protected $id = self::REDSTONE_LAMP;
    
    public function __construct($meta = 0) {
        $this->meta = $meta;
    }
    
    public function getName() {
        return 'Redstone Lamp';
    }
    
    public function getHardness() {
        return 0.3;
    }
    
    public function getLightLevel() {
        return 0;
    }
    
    public function getDrops(Item $item) {
        return [
            [self::REDSTONE_LAMP, 0, 1]
        ];
    }
    
}


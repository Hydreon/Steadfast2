<?php

namespace pocketmine\block;

class DragonEgg extends Solid {
    
    protected $id = self::DRAGON_EGG;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'Dragon Egg';
    }
    
    public function getLightLevel() {
        return 1;
    }
    
    public function getHardness() {
        return 3;
    }
    
}

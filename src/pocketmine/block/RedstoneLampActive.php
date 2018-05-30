<?php

namespace pocketmine\block;

class RedstoneLampActive extends RedstoneLamp {
    
    protected $id = self::REDSTONE_LAMP_ACTIVE;
    
    public function getLightLevel() {
        return 15;
    }
    
}

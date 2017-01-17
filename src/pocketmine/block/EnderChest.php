<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class EnderChest extends Transparent {
    
    protected $id = self::ENDER_CHEST;
    
    public function __construct($meta = 0){
		$this->meta = $meta;
	}
    
    public function getName() {
        return 'Ender Chest';
    }
    
    public function getToolType() {
        return Tool::TYPE_PICKAXE;
    }
    
    public function getHardness() {
        return 22.5;
    }
    
    public function getLightLevel(){
		return 7;
	}
    
    public function getDrops(Item $item) {
        return [
            [self::OBSIDIAN, 0, 8]
        ];
    }
    
    /** @todo place */
    /** @todo tile */
    /** @todo open */
    /** @todo inventory */
    /** @todo bunch of other things */
    
}

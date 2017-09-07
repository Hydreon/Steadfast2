<?php

namespace pocketmine\tile;

use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class EnderChest extends Spawnable  {
    
    public function getSpawnCompound(){
        $compound = new Compound("", [
            new StringTag("id", Tile::ENDER_CHEST),
            new IntTag("x", (int) $this->x),
            new IntTag("y", (int) $this->y),
            new IntTag("z", (int) $this->z)
        ]);

		if($this->hasName()){
			$compound->CustomName = $this->namedtag->CustomName;
		}

		return $compound;
	}
    
    public function hasName(){
		return isset($this->namedtag->CustomName);
	}
    
}

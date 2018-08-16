<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class RedSandstoneStairs extends SandstoneStairs {
	
	protected $id = self::RED_SANDSTONE_STAIRS;
	
	public function getName(){
		return "Red Sandstone Stairs";
	}
	
	public function getDrops(Item $item){
		if($item->isPickaxe() >= 1){
			return [
				[Item::RED_SANDSTONE_STAIRS, $this->meta & 0x03, 1],
			];
		}else{
			return [];
		}
	}
	
}

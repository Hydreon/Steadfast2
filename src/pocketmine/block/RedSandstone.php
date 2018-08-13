<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class RedSandstone extends Sandstone {
	
	protected $id = self::RED_SANDSTONE;
	
	public function getName(){
		return "Red Sandstone";
	}
	
	public function getDrops(Item $item){
		if($item->isPickaxe() >= 1){
			return [
				[Item::RED_SANDSTONE, $this->meta & 0x03, 1],
			];
		}else{
			return [];
		}
	}
	
}

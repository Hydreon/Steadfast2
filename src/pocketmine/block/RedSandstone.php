<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class RedSandstone extends Sandstone {
	
	protected $id = self::RED_SANDSTONE;
	
	public function getName(){
		static $names = [
			0 => "Red Sandstone",
			1 => "Chiseled Red Sandstone",
			2 => "Smooth Red Sandstone",
			3 => "",
		];
		return $names[$this->meta & 0x03];
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

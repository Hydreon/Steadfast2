<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class RedNetherBrick extends NetherBrick {

	protected $id = self::RED_NETHER_BRICK;
	
	public function getName() {
		return "Red Nether Brick";
	}

	public function getDrops(Item $item) {
		return [
			[Item::RED_NETHER_BRICK, 0, 1],
		];
	}
}

<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\item\Item;

class Repeater extends Item {

	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::REDSTONE_REPEATER, $meta, $count, "Redstone Repeater");
		$this->block = Block::get(Block::REDSTONE_REPEATER_BLOCK);
	}

}

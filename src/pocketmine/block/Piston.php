<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\Player;

class Piston extends Solid {
	
	protected $id = self::PISTON;
	
	public function __construct($meta = 0) {
		$this->meta = $meta;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}
	
}

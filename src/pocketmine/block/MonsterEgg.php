<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class MonsterEgg extends Solid {

	protected $id = self::MONSTER_EGG;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function getHardness() {
		return 2;
	}

	public function getName() {
		return "Monster Egg";
	}

	public function getDrops(Item $item) {
		return [
			[$this->id, $this->meta, 1],
		];
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}

}

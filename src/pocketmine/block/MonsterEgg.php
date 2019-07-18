<?php

namespace pocketmine\block;

use pocketmine\item\Item;

class MonsterEgg extends Solid {

	const STONE = 0;
	const COBBLESTONE = 1;
	const STONE_BRICKS = 2;
	const MOSSY_STONE_BRICKS = 3;
	const CRACKED_STONE_BRICKS = 4;
	const CHISELED_STONE_BRICKS = 5;

	protected $id = self::MONSTER_EGG;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function getHardness() {
		return 0.75;
	}

	public function getName() {
		static $names = [
			self::STONE => "Infested Stone",
			self::COBBLESTONE => "Cobblestone",
			self::STONE_BRICK => "Stone Bricks",
			self::MOSSY_STONE_BRICKS => "Mossy Stone Bricks",
			self::CRACKED_STONE_BRICKS => "Cracked Stone Bricks",
			self::CHISELED_STONE_BRICKS => "Chiseled Stone Bricks",
		];
		return $names[$this->meta & 0x05];
	}

	public function getDrops(Item $item) {
		return [];
	}

}

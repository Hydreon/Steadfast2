<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;

class NetherQuartzOre extends Solid {

	protected $id = self::NETHER_QUARTZ_ORE;

	public function __construct() {
		
	}

	public function getHardness() {
		return 3;
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}

	public function getName() {
		return "Nether Quartz Ore";
	}

	public function getDrops(Item $item) {
		if ($item->isPickaxe() >= 1) {
			return [
				[Item::NETHER_QUARTZ, 0, 1],
			];
		} else {
			return [];
		}
	}

}

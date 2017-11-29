<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\Player;

class IronTrapdoor extends Trapdoor {

	protected $id = self::IRON_TRAPDOOR;

	public function getName() {
		return "Iron Trapdoor";
	}

	public function getHardness() {
		return 5;
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}
	
	public function onUpdate($type) {
		if ($type == Level::BLOCK_UPDATE_NORMAL) {
			$this->meta ^= 0x08;
			$this->getLevel()->setBlock($this, $this, true, false);
		}
	}
	
	public function onActivate(Item $item, Player $player = null) {
		return false;
	}

}

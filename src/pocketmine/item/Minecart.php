<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Minecart as MinecartEntity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\Player;

class Minecart extends Item {

	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::MINECART, $meta, $count, "Minecart");
	}

	public function canBeActivated() {
		return true;
	}

	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz) {
		$chunk = $player->getLevel()->getChunk($block->getX() >> 4,  $block->getZ() >> 4, true);
		$minecart = new MinecartEntity($chunk, new Compound("", [
			"Pos" => new Enum("Pos", [
				new DoubleTag("", $block->getX()),
				new DoubleTag("", $block->getY()),
				new DoubleTag("", $block->getZ())
					]),
			"Motion" => new Enum("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
					]),
			"Rotation" => new Enum("Rotation", [
				new FloatTag("", 0),
				new FloatTag("", 0)
					]),
		]));
		$minecart->spawnToAll();
		if ($player->isSurvival()) {
			$item = $player->getInventory()->getItemInHand();
			$count = $item->getCount();
			if (--$count <= 0) {
				$player->getInventory()->setItemInHand(Item::get(Item::AIR));
				return true;
			}

			$item->setCount($count);
			$player->getInventory()->setItemInHand($item);
		}

		return true;
	}
	
	public function getMaxStackSize() {
		return 1;
	}

}

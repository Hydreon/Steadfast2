<?php

namespace pocketmine\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\Enum;
use pocketmine\network\multiversion\Entity as Multiversion;
use pocketmine\Player;

class Minecart extends Item {

	public function __construct($meta = 0, $count = 1) {
		parent::__construct(self::MINECART, $meta, $count, "Minecart");
	}

	public function canBeActivated() {
		return true;
	}

	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz) {
		if (self::isRail($target->getId())) {
			$chunk = $player->getLevel()->getChunk($block->getX() >> 4,  $block->getZ() >> 4, true);
			$nbt = new Compound("", [
				"Pos" => new Enum("Pos", [
					new DoubleTag("", $target->getX() + 0.5),
					new DoubleTag("", $target->getY()),
					new DoubleTag("", $target->getZ() + 0.5)
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
			]);
			$minecart = Entity::createEntity("Minecart", $chunk, $nbt);
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
		return false;
	}
	
	public function getMaxStackSize() {
		return 1;
	}

	private static function isRail($blockId) {
		return in_array($blockId, [Block::RAIL, Block::ACTIVATOR_RAIL, Block::DETECTOR_RAIL, Block::POWERED_RAIL]);
	}

}

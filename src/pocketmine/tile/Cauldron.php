<?php

namespace pocketmine\tile;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Spawnable;

class Cauldron extends Spawnable {
	
	/** @todo add potionId */
	/** @todo add splash potion checking */
	/** @todo add isMovable logic */
	
	public function getSpawnCompound() {
		$compound = new Compound("", [
            new StringTag("id", Tile::CAULDRON),
            new IntTag("x", (int) $this->x),
            new IntTag("y", (int) $this->y),
            new IntTag("z", (int) $this->z),
            new ShortTag("PotionId", -1),
            new ByteTag("SplashPotion", 0),
            new ByteTag("isMovable", 1),
        ]);

		return $compound;
	}
	
}

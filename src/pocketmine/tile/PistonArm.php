<?php

namespace pocketmine\tile;

use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class PistonArm extends Spawnable {
	
	public function getSpawnCompound() {
		return new Compound("", [
			new StringTag("id", Tile::PISTON_ARM),
			new IntTag("x", (int)$this->x),
			new IntTag("y", (int)$this->y),
			new IntTag("z", (int)$this->z),
			new FloatTag("Progress", $this->namedtag['Progress']),
			new ByteTag("State", $this->namedtag['State']),
		]);
	}

}

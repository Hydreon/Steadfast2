<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Tile;

class Piston extends Solid {
	
	protected $id = self::PISTON;
	
	public function __construct($meta = 0) {
		parent::__construct($this->id, $meta);
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		var_dump($face);
		$this->meta = $face;
		switch ($this->meta) {
			case 0:
				$side = self::SIDE_DOWN;
				break;
			case 1:
				$side = self::SIDE_UP;
				break;
			case 2:
				$side = self::SIDE_NORTH;
				$this->meta = 3;
				break;
			case 3:
				$side = self::SIDE_SOUTH;
				$this->meta = 2;
				break;
			case 4:
				$side = self::SIDE_WEST;
				$this->meta = 5;
				break;
			case 5:
				$side = self::SIDE_EAST;
				$this->meta = 4;
				break;
		}
		$this->meta += 128;
		$isWasPlaced = $this->getLevel()->setBlock($this, $this, true, true);
		if ($isWasPlaced) {
			$side = $this->getSide($side);
			$this->getLevel()->setBlock($side, Block::get(self::PISTON_HEAD), true, true);
			
			$nbt = new Compound("", [
				new StringTag("id", Tile::PISTON_ARM),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
				new FloatTag("Progress", 1.0),
			]);
			$chunk = $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4);
			Tile::createTile(Tile::PISTON_ARM, $chunk, $nbt);
		}
	}
	
}

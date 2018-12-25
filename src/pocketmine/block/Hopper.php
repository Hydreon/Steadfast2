<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Hopper as HopperTile;
use pocketmine\tile\Tile;

class Hopper extends Transparent {

	protected $id = self::HOPPER_BLOCK;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function canBeActivated() {
		return true;
	}

	public function getHardness() {
		return 3;
	}

	public function getName() {
		return "Hopper";
	}

	public function getToolType() {
		return Tool::TYPE_PICKAXE;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		$faces = [
			0 => 0,
			1 => 0,
			2 => 3,
			3 => 2,
			4 => 5,
			5 => 4
		];
		$this->meta = $faces[$face];
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}

	public function onActivate(Item $item, Player $player = null) {
		$tile = $this->level->getTile($this);
		if (!($tile instanceof HopperTile)) {
			$nbt = new Compound("", [
				new Enum("Items", []),
				new StringTag("id", Tile::HOPPER),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			$nbt->Items->setTagType(NBT::TAG_Compound);
			$tile = Tile::createTile(Tile::HOPPER, $this->level->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		}
		$player->addWindow($tile->getInventory());
		return true;
	}

	public function getDrops(Item $item) {
		return [
			[$this->id, 0, 1],
		];
	}

}

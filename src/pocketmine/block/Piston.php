<?php

namespace pocketmine\block;

use pocketmine\block\Solid;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
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
	
	public function getFace() {
		return $this->meta & 0x07; // first 3 bits is face
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		$this->meta = $face;
		switch ($this->meta) {
			case 2:
				$this->meta = 3;
				break;
			case 3:
				$this->meta = 2;
				break;
			case 4:
				$this->meta = 5;
				break;
			case 5:
				$this->meta = 4;
				break;
		}
		$this->meta += 128;
		$isWasPlaced = $this->getLevel()->setBlock($this, $this, true, true);
		if ($isWasPlaced) {
			$nbt = new Compound("", [
				new StringTag("id", Tile::PISTON_ARM),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
				new FloatTag("Progress", 0.0),
				new ByteTag("State", 0),
			]);
			$chunk = $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4);
			Tile::createTile(Tile::PISTON_ARM, $chunk, $nbt);
			$this->onUpdate(Level::BLOCK_UPDATE_NORMAL);
		}
	}
	
	public function onUpdate($type) {
		$face = $this->getFace();
		switch ($face) {
			case 0:
				$sideToExtend = self::SIDE_DOWN;
				break;
			case 1:
				$sideToExtend = self::SIDE_UP;
				break;
			case 2:
				$sideToExtend = self::SIDE_NORTH;
				break;
			case 3:
				$sideToExtend = self::SIDE_SOUTH;
				break;
			case 4:
				$sideToExtend = self::SIDE_WEST;
				break;
			case 5:
				$sideToExtend = self::SIDE_EAST;
				break;
			default:
				return;
		}
		static $offsets = [
			self::SIDE_NORTH => [0, 0, -1],
			self::SIDE_SOUTH => [0, 0, 1],
			self::SIDE_EAST => [1, 0, 0],
			self::SIDE_WEST => [-1, 0, 0],
			self::SIDE_UP => [0, 1, 0],
			self::SIDE_DOWN => [0, -1, 0],
		];
		$isShouldBeExpanded = false;
		foreach ($offsets as $side => $offset) {
			if ($side == $sideToExtend) {
				continue;
			}
			$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
			switch ($blockId) {
				case self::REDSTONE_TORCH_ACTIVE:
					$isShouldBeExpanded = true;
					break 2;
				case self::REDSTONE_WIRE:
					$wirePower = $this->level->getBlockDataAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
					if ($wirePower > 0) {
						$isShouldBeExpanded = true;
						break 2;
					}
					break;
				default:
					if (isset(Block::$solid[$blockId]) && Block::$solid[$blockId]) {
						$vector = new Vector3($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
						$block = $this->level->getBlock($vector);
						if ($block->getPoweredState() != Solid::POWERED_NONE) {
							$isShouldBeExpanded = true;
							break 2;
						}
					}
					break;
			}
		}
		$pistonTile = $this->level->getTile($this);
		if ($pistonTile !== null) {
			if ($isShouldBeExpanded && $pistonTile->namedtag['Progress'] < 1) {
				$expandBlock = $this->getSide($sideToExtend);
				$this->getLevel()->setBlock($expandBlock, Block::get(self::PISTON_HEAD), true, true);
				$pistonTile->namedtag['Progress'] = 1;
				$pistonTile->namedtag['State'] = 2;
				$pistonTile->spawnToAll();
			} else if (!$isShouldBeExpanded && $pistonTile->namedtag['Progress'] > 0) {
				$expandBlock = $this->getSide($sideToExtend);
				$this->getLevel()->setBlock($expandBlock, Block::get(self::AIR), true, true);
				$pistonTile->namedtag['Progress'] = 0;
				$pistonTile->namedtag['State'] = 0;
				$pistonTile->spawnToAll();
			}
		}
	}
	
}

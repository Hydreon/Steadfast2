<?php

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

class RedstoneComparator extends Transparent {

	const FACE_NORTH = 0;
	const FACE_EAST = 1;
	const FACE_SOUTH = 2;
	const FACE_WEST = 3;

	protected $id = self::REDSTONE_COMPARATOR_BLOCK;

	public function __construct($meta = 0) {
		$this->meta = $meta;
	}

	public function canBeFlowedInto() {
		return true;
	}

	public function canBeActivated() {
		return true;
	}

	public function getHardness() {
		return 1;
	}

	public function getResistance() {
		return 0;
	}

	public function getBoundingBox() {
		return null;
	}

	public function getDrops(Item $item) {
		return [
			[Item::REDSTONE_COMPARATOR, 0, 1],
		];
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		switch ($face) {
			case 1:
				if ($player->yaw <= 45 || $player->yaw >= 315) { // south
					$this->meta = self::FACE_SOUTH;
				} else if ($player->yaw >= 135 && $player->yaw <= 225) { // north
					$this->meta = self::FACE_NORTH;
				} else if ($player->yaw > 45 && $player->yaw < 135) { // west
					$this->meta = self::FACE_WEST;
				} else { // east
					$this->meta = self::FACE_EAST;
				}
				break;
			case 0:
			case 2:
			case 3:
			case 4:
			case 5:
			default:
				return false; // wrong face
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}

	public function getBackBlockCoords() {
		$face = $this->meta & 0x03;
		switch ($face) {
			case self::FACE_NORTH:
				return new Vector3($this->x, $this->y, $this->z + 1);
			case self::FACE_EAST:
				return new Vector3($this->x - 1, $this->y, $this->z);
			case self::FACE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z - 1);
			case self::FACE_WEST:
				return new Vector3($this->x + 1, $this->y, $this->z);
		}
	}

	public function getFrontBlockCoords() {
		$face = $this->meta & 0x03;
		switch ($face) {
			case self::FACE_NORTH:
				return new Vector3($this->x, $this->y, $this->z - 1);
			case self::FACE_EAST:
				return new Vector3($this->x + 1, $this->y, $this->z);
			case self::FACE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z + 1);
			case self::FACE_WEST:
				return new Vector3($this->x - 1, $this->y, $this->z);
		}
	}

	public function getFace() {
		return $this->meta & 0x03;
	}
	
	public function isActive() {
		return $this->meta >> 3;
	}
	
	public function needScheduleOnUpdate() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		if ($type == Level::BLOCK_UPDATE_NORMAL) {
			$backPosition = $this->getBackBlockCoords();
			$backBlockID = $this->level->getBlockIdAt($backPosition->x, $backPosition->y, $backPosition->z);
			if ($backBlockID == self::CHEST) {
				$chestInventory = $this->level->getTile($backPosition)->getInventory();
				if ($this->isActive() && $chestInventory->firstNotEmpty() == -1) {
					$this->meta &= 0x07;
					$this->level->setBlock($this, $this, true, true, $deep);
				} else if (!$this->isActive() && $chestInventory->firstNotEmpty() != -1) {
					$this->meta |= 0x08;
					$this->level->setBlock($this, $this, true, true, $deep);
				}
			}
		}
	}

}

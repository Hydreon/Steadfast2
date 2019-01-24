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
		$isWasPlaced = $this->getLevel()->setBlock($this, $this, true, true);
		if ($isWasPlaced) {
			$nbt = new Compound("", [
				new StringTag("id", Tile::PISTON_ARM),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
				new FloatTag("Progress", 0.0),
				new ByteTag("State", 0),
				new ByteTag("HaveCharge", 0),
			]);
			$chunk = $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4);
			Tile::createTile(Tile::PISTON_ARM, $chunk, $nbt);
			$this->onUpdate(Level::BLOCK_UPDATE_NORMAL, 0);
		}
	}
	
	protected function getExtendSide() {
		$face = $this->getFace();
		switch ($face) {
			case 0:
				return self::SIDE_DOWN;
			case 1:
				return self::SIDE_UP;
			case 2:
				return self::SIDE_SOUTH;
			case 3:
				return self::SIDE_NORTH;
			case 4:
				return self::SIDE_EAST;
			case 5:
				return self::SIDE_WEST;
		}
		return null;
	}
	
	public function needScheduleOnUpdate() {
		return true;
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		if ($type != Level::BLOCK_UPDATE_TOUCH) {
			static $offsets = [
				self::SIDE_NORTH => [0, 0, -1],
				self::SIDE_SOUTH => [0, 0, 1],
				self::SIDE_EAST => [1, 0, 0],
				self::SIDE_WEST => [-1, 0, 0],
				self::SIDE_UP => [0, 1, 0],
				self::SIDE_DOWN => [0, -1, 0],
			];
			$sideToExtend = $this->getExtendSide();
			if ($sideToExtend === null) {
				return;
			}
			$isShouldBeExpanded = false;
			foreach ($offsets as $side => $offset) {
				if ($side == $sideToExtend) {
					continue;
				}
				$blockId = $this->level->getBlockIdAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
				switch ($blockId) {
					case self::REDSTONE_TORCH_ACTIVE:
					case self::REDSTONE_REPEATER_BLOCK_ACTIVE:
						$isShouldBeExpanded = true;
						break 2;
					case self::REDSTONE_WIRE:
						$wirePower = $this->level->getBlockDataAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
						if ($wirePower > 0) {
							$isShouldBeExpanded = true;
							break 2;
						}
						break;
					case self::LEVER:
						$leverMeta =  $this->level->getBlockDataAt($this->x + $offset[0], $this->y + $offset[1], $this->z + $offset[2]);
						if ((($leverMeta >> 3) & 0x01)) {
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
//				echo "X: " . $this->x . " Z: " . $this->z . " Update piston" . PHP_EOL;
				if ($isShouldBeExpanded && $pistonTile->namedtag['Progress'] < 1) {
					if ($this->isMayBeExtended()) {
						$this->extend($pistonTile, $sideToExtend, $deep);
					} else {
//						echo "X: " . $this->x . " Z: " . $this->z . " Piston recive charge" . PHP_EOL;
//						$pistonTile->namedtag['HaveCharge'] = 1;
						$this->level->scheduleUpdate($this, 1);
					}
				} else if (!$isShouldBeExpanded && $pistonTile->namedtag['Progress'] > 0) {
//					var_dump($pistonTile->namedtag);
					$this->retract($pistonTile, $sideToExtend, $deep);
				} else {
					if ($pistonTile->namedtag['HaveCharge'] && $this->isMayBeExtended()) {
						$this->extend($pistonTile, $sideToExtend, $deep);
					} else {
//						var_dump("Piston remove charge 1 " . $this->x . " " . $this->z);
//						$pistonTile->namedtag['HaveCharge'] = 0;
					}
				}
			}
		}
	}
	
	protected function extend($tile, $extendSide, $deep) {
//		echo "X: " . $this->x . " Z: " . $this->z . " Extend piston" . PHP_EOL;
		$tile->namedtag['Progress'] = 1;
		$tile->namedtag['State'] = 2;
//		$tile->namedtag['HaveCharge'] = 0;
		$extendBlock = $this->moveNextBlocks($extendSide, $deep);
		$this->getLevel()->setBlock($extendBlock, Block::get(self::PISTON_HEAD), true, true, $deep);
//		var_dump("Piston remove charge 2 " . $this->x . " " . $this->z);
		$tile->spawnToAll();
	}
	
	protected function moveNextBlocks($extendSide, $deep) {
		$extendBlock = $this->getSide($extendSide);
		$resultBlock = clone $extendBlock;
		if ($extendBlock->getId() !== self::AIR && $extendBlock->getId() != self::PISTON_HEAD && !$extendBlock->isMayBeDestroyedByPiston()) {	
			$anotherBlock = clone $extendBlock->getSide($extendSide);
			if ($extendBlock instanceof Piston) {
				$extendBlock->moveNextBlocks($extendSide, $deep);
			}
			if (!is_null($oldTile  = $this->level->getTile($extendBlock))) {
				$oldTile->updatePosition($anotherBlock->x, $anotherBlock->y, $anotherBlock->z);
			}	
			$this->getLevel()->setBlock($anotherBlock, $extendBlock, true, true, $deep);
		}
		return $resultBlock;
	}

	protected function retract($tile, $extendSide, $deep) {
//		echo "X: " . $this->x . " Z: " . $this->z . " Retract piston" . PHP_EOL;
		$tile->namedtag['Progress'] = 0;
		$tile->namedtag['State'] = 0;
//		$tile->namedtag['HaveCharge'] = 0;
		$extendBlock = $this->getSide($extendSide);
		$this->getLevel()->setBlock($extendBlock, Block::get(self::AIR), true, true, $deep);
//		var_dump("Piston remove charge 3 " . $this->x . " " . $this->z);
		$tile->spawnToAll();
	}

	public function isMayBeExtended() {
		$sideToExtend = $this->getExtendSide();
		if ($sideToExtend === null) {
			return false;
		}
		$firstBlock = $this->getSide($sideToExtend);
		if ($firstBlock->getId() == self::AIR || $firstBlock->isMayBeDestroyedByPiston()) {
			return true;
		} else if (self::$solid[$firstBlock->getId()]) {	
			$secondBlock = $firstBlock->getSide($sideToExtend);
			if ($secondBlock->getId() == self::AIR || $secondBlock->isMayBeDestroyedByPiston()) {
				return true;
			}
			if (($firstBlock instanceof Piston) && ($this->getFace() == $firstBlock->getFace()) && $firstBlock->isMayBeExtended()) {
				return true;
			}
		}
		return false;
	}
	
}

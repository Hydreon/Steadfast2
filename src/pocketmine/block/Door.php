<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
 */

namespace pocketmine\block;

use pocketmine\block\Solid;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Door extends Transparent {

	public function canBeActivated() {
		return true;
	}

	public function isSolid() {
		return false;
	}
	
	public function isOpen() {
		$isTopPart = ($this->getDamage() & 0x08) === 0x08;
		if ($isTopPart) {
			$bottom = $this->getSide(Vector3::SIDE_DOWN);
			return ($bottom->getDamage() & 0x04) >> 2;
		}
		return ($this->meta & 0x04) >> 2;
	}

	private function getFullDamage() {
		$damage = $this->getDamage();
		$isUp = ($damage & 0x08) > 0;

		if ($isUp) {
			$down = $this->getSide(Vector3::SIDE_DOWN)->getDamage();
			$up = $damage;
		} else {
			$down = $damage;
			$up = $this->getSide(Vector3::SIDE_UP)->getDamage();
		}

		$isRight = ($up & 0x01) > 0;

		return $down & 0x07 | ($isUp ? 8 : 0) | ($isRight ? 0x10 : 0);
	}

	protected function recalculateBoundingBox() {
		$f = 0.1875;
		$damage = $this->getFullDamage();

		$bb = new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + 2, $this->z + 1);

		$face = $damage & 0x03;
		$isOpen = (($damage & 0x04) > 0);
		$isRight = (($damage & 0x10) > 0);

		switch ($face) {
			case 0:
				if ($isOpen) {
					if (!$isRight) {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
				}
				break;
			case 1:
				if ($isOpen) {
					if (!$isRight) {
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
				}
				break;
			case 2:
				if ($isOpen) {
					if (!$isRight) {
						$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
					}
				} else {
					$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
			case 3:
				if ($isOpen) {
					if (!$isRight) {
						$bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
					} else {
						$bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
					}
				} else {
					$bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
				}
				break;
		}
		return $bb;
	}

	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		switch ($type) {
			case Level::BLOCK_UPDATE_NORMAL:
				if ($this->getSide(Vector3::SIDE_DOWN)->getId() === self::AIR) { //Replace with common break method
					$this->getLevel()->setBlock($this, new Air(), false, true, $deep);
					if ($this->getSide(Vector3::SIDE_UP) instanceof Door) {
						$this->getLevel()->setBlock($this->getSide(1), new Air(), false, true, $deep);
					}
					return Level::BLOCK_UPDATE_NORMAL;
				}
				break;
			case Level::BLOCK_UPDATE_SCHEDULED:
				break;
		}
		return false;
	}

	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		if ($face === 1) {
			$blockUp = $this->getSide(1);
			$blockDown = $this->getSide(0);
			if ($blockUp->canBeReplaced() === false or $blockDown->isTransparent() === true) {
				return false;
			}
			$direction = $player instanceof Player ? $player->getDirection() : 0;
			static $face = [
				0 => 3,
				1 => 4,
				2 => 2,
				3 => 5,
			];
			$next = $this->getSide($face[($direction + 2) % 4]);
			$next2 = $this->getSide($face[$direction]);
			$metaUp = 0x08;
			if ($next->getId() === $this->getId() or ( $next2->isTransparent() === false and $next->isTransparent() === true)) { //Door hinge
				$metaUp |= 0x01;
			}

			$this->setDamage($direction & 0x03);
			$this->getLevel()->setBlock($block, $this, true, true); //Bottom
			$this->getLevel()->setBlock($blockUp, $b = Block::get($this->getId(), $metaUp), true); //Top
			return true;
		}

		return false;
	}

	public function onBreak(Item $item) {
		if (($this->getDamage() & 0x08) === 0x08) {
			$down = $this->getSide(self::SIDE_DOWN);
			if ($down->getId() === $this->getId()) {
				$this->getLevel()->setBlock($down, new Air(), true);
			}
		} else {
			$up = $this->getSide(self::SIDE_UP);
			if ($up->getId() === $this->getId()) {
				$this->getLevel()->setBlock($up, new Air(), true);
			}
		}
		$this->getLevel()->setBlock($this, new Air(), true);

		return true;
	}

	public function onActivate(Item $item, Player $player = null) {
		return $this->toggleOpenState();
	}
	
	protected function toggleOpenState() {
		if (($this->getDamage() & 0x08) === 0x08) { //Top
			$down = $this->getSide(0); // get block below
			if ($down->getId() !== $this->getId()) { // if not door part
				return false;
			}
			$doorBottom = $down;
		} else {
			$doorBottom = $this;
		}
		$newMeta = $doorBottom->getDamage() ^ 0x04; // close if opened, open if close
		$doorBottom->setDamage($newMeta);
		$level = $doorBottom->getLevel();
		$level->setBlock($doorBottom, $doorBottom, true);
		$level->addSound(new DoorSound($doorBottom));
		return true;
	}
	
	public function isConnectedWithChargedBlock($isShouldCheckAnotherPart = true) {
		static $sides = [
			Vector3::SIDE_NORTH,
			Vector3::SIDE_SOUTH,
			Vector3::SIDE_WEST,
			Vector3::SIDE_EAST,
		];
		$isTopPart = ($this->getDamage() & 0x08) === 0x08;
		$block = $this->getSide($isTopPart ? Vector3::SIDE_UP : Vector3::SIDE_DOWN);
		if ($block->isCharged()) {
			return true;
		}
		// check sides
		foreach ($sides as $side) {
			$sideBlock = $this->getSide($side);
			if ($sideBlock->isCharged()) {
				return true;
			}
		}
		if ($isShouldCheckAnotherPart) {
			$anotherPart = $this->getSide($isTopPart ? Vector3::SIDE_DOWN : Vector3::SIDE_UP);
			if ($anotherPart instanceof Door) {
				return $anotherPart->isConnectedWithChargedBlock(false);
			}
		}
		return false;
	}

}

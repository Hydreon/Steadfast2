<?php

namespace pocketmine\block;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class Button extends Transparent{
	
	protected $activeTicks = 20; // 1 second
	
	public function getFace() {
		return $this->meta & 0x07;
	}
	
	public function isActive() {
		return $this->meta >> 3;
	}
	
	public function setActive($value) {
		if ($value) {
			$this->meta = $this->meta | 0x08;
		} else {
			$this->meta = $this->meta & 0x07;
		}
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		if (!$target->isSolid()) {
			return false;
		}
		$this->meta = $face;
		return $this->level->setBlock($block, $this, true, true);
	}
	
	public function onUpdate($type, $deep) {
		if (!Block::onUpdate($type, $deep)) {
			return false;
		}
		$deep++;
		switch ($type) {
			case Level::BLOCK_UPDATE_NORMAL:
				static $sides = [
					Block::FACE_DOWN => Vector3::SIDE_UP,
					Block::FACE_UP => Vector3::SIDE_DOWN,
					Block::FACE_EAST => Vector3::SIDE_WEST,
					Block::FACE_WEST => Vector3::SIDE_EAST,
					Block::FACE_SOUTH => Vector3::SIDE_NORTH,
					Block::FACE_NORTH => Vector3::SIDE_SOUTH,
				];
				// getting block on witch placed button
				$holderBlock = $this->getSide($sides[$this->getFace()]);
				if (!$holderBlock->isSolid()) {
					$this->level->useBreakOn($this);
				}
				break;
			case Level::BLOCK_UPDATE_SCHEDULED:
				$this->setActive(false);
				$this->level->setBlock($this, $this, false, true, $deep);
				break;
		}
	}
	
	public function getHardness() {
		return 0.5;
	}
	
	public function canBeActivated() {
		return true;
	}
		
	public function onActivate(Item $item, Player $player = null) {
		if (!$this->isActive()) {
			$this->setActive(true);
			$this->level->setBlock($this, $this);
			$this->level->scheduleUpdate($this, $this->activeTicks);
		}
	}
	
}
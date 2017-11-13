<?php

namespace pocketmine\block;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\Player;

class Dropper extends Solid {
	
	private $isWasActivated = false;
	
	public function __construct($meta = 0){
		$this->id = self::DROPPER;
		$this->meta = $meta;
	}
	
	public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null) {
		if ($player->pitch > 45) {
			$this->meta = 1;
		} else if ($player->pitch < -45) {
			$this->meta = 0;
		} else {
			if ($player->yaw <= 45 || $player->yaw > 315) {
				$this->meta = 2;
			} else if ($player->yaw > 45 && $player->yaw <= 135) {
				$this->meta = 5;
			} else if ($player->yaw > 135 && $player->yaw <= 225) {
				$this->meta = 3;
			} else {
				$this->meta = 4;
			}
		}
		return parent::place($item, $block, $target, $face, $fx, $fy, $fz, $player);
	}
	
	public function onUpdate($type) {
		// undone
	}
}

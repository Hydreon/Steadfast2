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

namespace pocketmine\inventory;

use pocketmine\inventory\InventoryType;
use pocketmine\level\Position;
use pocketmine\Player;

class EnchantInventory extends ContainerInventory {

	private $bookshelfAmount = 0;
	private $levels = [];
	protected $enchantingLevel = 0;

	public function __construct(Position $pos) {
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::ENCHANT_TABLE));
	}

	/**
	 * @return FakeBlockMenu
	 */
	public function getHolder() {
		return $this->holder;
	}

	public function onOpen(Player $who) {
		parent::onOpen($who);
		if ($this->levels == null) {
			$this->bookshelfAmount = $this->countBookshelf();
			$base = mt_rand(1, 8) + ($this->bookshelfAmount / 2) + mt_rand(0, $this->bookshelfAmount);
			$this->levels = [
				0 => max($base / 3, 1),
				1 => (($base * 2) / 3 + 1),
				2 => max($base, $this->bookshelfAmount * 2)
			];
		}
	}

	public function onClose(Player $who) {
		parent::onClose($who);

		for ($i = 0; $i < 2; $i++) {
			$this->getHolder()->getLevel()->dropItem($this->getHolder()->add(0.5, 0.5, 0.5), $this->getItem($i));
			$this->clear($i);
		}
	}

	public function countBookshelf() {
		$count = 0;
		$pos = $this->getHolder();
		$offsets = [[2, 0], [-2, 0], [0, 2], [0, -2], [2, 1], [2, -1], [-2, 1], [-2, 1], [1, 2], [-1, 2], [1, -2], [-1, -2]];
		for ($i = 0; $i < 3; $i++) {
			foreach ($offsets as $offset) {
				if ($pos->getLevel()->getBlockIdAt($pos->x + $offset[0], $pos->y + $i, $pos->z + $offset[1]) == Block::BOOKSHELF) {
					$count++;
				}
				if ($count === 15) {
					break 2;
				}
			}
		}
		return $count;
	}
	
	public function setEnchantingLevel($level) {
		$this->enchantingLevel = $level;
	}
	
	public function getEnchanringLevel($level) {
		return $this->enchantingLevel;
	}
	
	public function isItemWasEnchant() {
		return $this->enchantingLevel !== 0;
	}

}

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

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\TileEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;

class ChestInventory extends ContainerInventory {

	public function __construct(Chest $tile) {
		parent::__construct($tile, InventoryType::get(InventoryType::CHEST));
	}

	/**
	 * @return Chest
	 */
	public function getHolder() {
		return $this->holder;
	}

	public function onOpen(Player $who) {
		parent::onOpen($who);

		if (count($this->getViewers()) === 1) {
			$pk = new TileEventPacket();
			$pk->x = $this->getHolder()->getX();
			$pk->y = $this->getHolder()->getY();
			$pk->z = $this->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 2;
			if (($level = $this->getHolder()->getLevel()) instanceof Level) {
				Server::broadcastPacket($level->getUsingChunk($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4), $pk);
			}
		}
		$position = [ 'x' => $this->holder->x, 'y' => $this->holder->y, 'z' => $this->holder->z ];
		$who->sendSound(LevelSoundEventPacket::SOUND_CHEST_OPEN, $position);
	}

	public function onClose(Player $who) {
		if (count($this->getViewers()) === 1) {
			$pk = new TileEventPacket();
			$pk->x = $this->getHolder()->getX();
			$pk->y = $this->getHolder()->getY();
			$pk->z = $this->getHolder()->getZ();
			$pk->case1 = 1;
			$pk->case2 = 0;
			if (($level = $this->getHolder()->getLevel()) instanceof Level) {
				Server::broadcastPacket($level->getUsingChunk($this->getHolder()->getX() >> 4, $this->getHolder()->getZ() >> 4), $pk);
			}
		}
		parent::onClose($who);
		$position = [ 'x' => $this->holder->x, 'y' => $this->holder->y, 'z' => $this->holder->z ];
 		$who->sendSound(LevelSoundEventPacket::SOUND_CHEST_CLOSED, $position);
	}
	
	public function setItem($index, Item $item, $needCheckComporator = true) {		
		if (parent::setItem($index, $item)) {	
			if ($needCheckComporator) {
				if (!is_null($this->holder->level)) {
					$isShouldUpdateBlock = $item->getId() != Item::AIR && !$item->equals($this->getItem($index));
					if ($isShouldUpdateBlock) {
						$this->holder->getBlock()->onUpdate(Level::BLOCK_UPDATE_WEAK, 0);
					}			
					static $offsets = [
						[1, 0, 0],
						[-1, 0, 0],
						[0, 0, -1],
						[0, 0, 1],
					];
					$tmpVector = new Vector3(0, 0, 0);
					foreach ($offsets as $offset) {
						$tmpVector->setComponents($this->holder->x + $offset[0], $this->holder->y, $this->holder->z + $offset[2]);
						if ($this->holder->level->getBlockIdAt($tmpVector->x, $tmpVector->y, $tmpVector->z) == Block::REDSTONE_COMPARATOR_BLOCK) {
							$comparator = $this->holder->level->getBlock($tmpVector);
							$comparator->onUpdate(Level::BLOCK_UPDATE_NORMAL,  0);
						}
					}
				}
			}
			return true;
		}
		return false;
	}

}

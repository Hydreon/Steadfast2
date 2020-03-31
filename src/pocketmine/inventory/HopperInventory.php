<?php

namespace pocketmine\inventory;

use pocketmine\inventory\InventoryType;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\protocol\TileEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Hopper;

class HopperInventory extends ContainerInventory {
	
	public function __construct(Hopper $tile) {
		parent::__construct($tile, InventoryType::get(InventoryType::HOPPER));
	}

	/**
	 * @return Hopper
	 */
	public function getHolder() {
		return $this->holder;
	}

	public function onOpen(Player $who) {
		parent::onOpen($who);
		if (count($this->getViewers()) === 1) {
			$pk = new TileEventPacket();
			$pk->x = $this->holder->getX();
			$pk->y = $this->holder->getY();
			$pk->z = $this->holder->getZ();
			$pk->case1 = 1;
			$pk->case2 = 2;
			if (($level = $this->holder->getLevel()) instanceof Level) {
				Server::broadcastPacket($level->getUsingChunk($this->holder->getX() >> 4, $this->holder->getZ() >> 4), $pk);
			}
		}
	}

	public function onClose(Player $who) {
		if (count($this->getViewers()) === 1) {
			$pk = new TileEventPacket();
			$pk->x = $this->holder->getX();
			$pk->y = $this->holder->getY();
			$pk->z = $this->holder->getZ();
			$pk->case1 = 1;
			$pk->case2 = 0;
			if (($level = $this->holder->getLevel()) instanceof Level) {
				Server::broadcastPacket($level->getUsingChunk($this->holder->getX() >> 4, $this->holder->getZ() >> 4), $pk);
			}
		}
		parent::onClose($who);
	}
	
	/**
	 * 
	 * @return Item | null
	 */
	public function getFirstItem(&$itemIndex) {
		foreach($this->getContents() as $index => $item){
			if ($item->getId() != Item::AIR && $item->getCount() >= 0) {
				$itemIndex = $index;
				return $item;
			}
		}
		return null;
	}
	
}

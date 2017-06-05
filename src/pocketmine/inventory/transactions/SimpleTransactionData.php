<?php

namespace pocketmine\inventory\transactions;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory120;
use pocketmine\item\Item;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\Player;

class SimpleTransactionData {
	
	/** @var integer */
	public $inventoryId = -1;
	/** @var integer */
	public $slot = -1;
	/** @var Item */
	public $oldItem;
	/** @var Item */
	public $newItem;
	
	public function __construct() {
		$this->oldItem = Item::get(Item::AIR);
		$this->newItem = Item::get(Item::AIR);
	}
	
	public function __toString() {
		return 'Inv.ID: ' . $this->inventoryId . PHP_EOL .
				'Slot: ' . $this->slot . PHP_EOL .
				'Old item: ' . $this->oldItem . PHP_EOL .
				'New item: ' . $this->newItem . PHP_EOL;
	}
	
	/**
	 * source - old
	 * target - new
	 * @param Player $player
	 * @return BaseTransaction
	 */
	public function convertToTransaction($player) {
		switch ($this->inventoryId) {
			case Protocol120::CONTAINER_ID_INVENTORY:
				$inventory = $player->getInventory();
				$slot = $this->slot;
				break;
			case Protocol120::CONTAINER_ID_CURSOR_SELECTED:
				$inventory = $player->getInventory();
				$slot = PlayerInventory120::CURSOR_INDEX;
				break;
			case Protocol120::CONTAINER_ID_ARMOR:
				$inventory = $player->getInventory();
				$slot = $inventory->getSize() + $this->slot;
				break;
			default:
				$inventory = $player->getInventory();
				$slot = $this->slot;
				break;
			
		}
		return new BaseTransaction($inventory, $slot, $this->oldItem, $this->newItem);
	}
}

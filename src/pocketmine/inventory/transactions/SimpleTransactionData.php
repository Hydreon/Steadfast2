<?php

namespace pocketmine\inventory\transactions;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory120;
use pocketmine\item\Item;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\Player;

class SimpleTransactionData {
	
	/**
	 * @INPORTANT don't use CRAFT_ACTION outside this class, it will change with new spec 
	 */
	const CRAFT_ACTION_PUT_SLOT = 3;
	const CRAFT_ACTION_GET_SLOT = 5;
	const CRAFT_ACTION_GET_RESULT = 7;
	const CRAFT_ACTION_USE = 9;
	
	/** @var integer */
	/** @important for InventoryTransactionPacket */
	public $sourceType = 0;
	/** @var integer */
	public $inventoryId = -1;
	/** @var integer */
	public $slot = -1;
	/** @var Item */
	public $oldItem;
	/** @var Item */
	public $newItem;
	/** @var integer */
	public $craftAction = -1;
	
	public function __construct() {
		$this->oldItem = Item::get(Item::AIR);
		$this->newItem = Item::get(Item::AIR);
	}
	
	public function __toString() {
		return 'Inv.ID: ' . $this->inventoryId . PHP_EOL .
				'Craft action: ' . $this->craftAction . PHP_EOL .
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
			case Protocol120::CONTAINER_ID_NONE:
				$currentWindowId = $player->getCurrentWindowId();
				if ($currentWindowId != $this->inventoryId) {
					var_dump('it maybe big craft packet check it please');
					return null;
				}
				$inventory = $player->getInventory();
				switch ($this->craftAction) {
					case self::CRAFT_ACTION_GET_RESULT:
						$slot = PlayerInventory120::CRAFT_RESULT_INDEX;
						break;
					default:
						$slot = PlayerInventory120::CRAFT_INDEX_0 - $this->slot;
						break;
				}
				break;
			default:
				$currentWindowId = $player->getCurrentWindowId();
				if ($currentWindowId != $this->inventoryId) {
					return null;
				}
				$inventory = $player->getCurrentWindow();
				$slot = $this->slot;
				break;
			
		}
		return new BaseTransaction($inventory, $slot, $this->oldItem, $this->newItem);
	}
}

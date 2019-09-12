<?php

namespace pocketmine\inventory\transactions;

use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\network\protocol\v120\InventoryTransactionPacket;
use pocketmine\network\protocol\v120\Protocol120;
use pocketmine\Player;

class SimpleTransactionData {
	
	const ACTION_CRAFT_PUT_SLOT = 3;
	const ACTION_CRAFT_GET_SLOT = 5;
	const ACTION_CRAFT_GET_RESULT = 7;
	const ACTION_CRAFT_USE = 9;
	
	const ACTION_ENCH_ITEM = 29;
	const ACTION_ENCH_LAPIS = 31;
	const ACTION_ENCH_RESULT = 33;
	
	const ACTION_DROP = 199;
	
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
	public $action = -1;
	/** @var integer */
	public $flags = 0;
	
	public function __construct() {
		$this->oldItem = Item::get(Item::AIR);
		$this->newItem = Item::get(Item::AIR);
	}
	
	public function __toString() {
		return 'Source type: ' . $this->sourceType . PHP_EOL .
				'Inv.ID: ' . $this->inventoryId . PHP_EOL .
				'Action: ' . $this->action . PHP_EOL .
				'Flags: ' . $this->flags . PHP_EOL .
				'Slot: ' . $this->slot . PHP_EOL .
				'Old item: ' . $this->oldItem . PHP_EOL .
				'New item: ' . $this->newItem . PHP_EOL;
	}
	
	public function isDropItemTransaction() {
		return $this->sourceType == InventoryTransactionPacket::INV_SOURCE_TYPE_WORLD_INTERACTION && 
				$this->inventoryId == Protocol120::CONTAINER_ID_NONE;
	}
	
	public function isCompleteEnchantTransaction() {
		return $this->action == self::ACTION_ENCH_RESULT;
	}

	public function isUpdateEnchantSlotTransaction() {
		return $this->action == self::ACTION_ENCH_ITEM || $this->action == self::ACTION_ENCH_LAPIS || ($this->inventoryId == Protocol120::CONTAINER_ID_CURSOR_SELECTED && ($this->slot == 14 || $this->slot == 15));
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
				if($this->slot == 0) {
					$slot = PlayerInventory::CURSOR_INDEX;
				} elseif ($this->slot == 50) {
					$slot = PlayerInventory::CRAFT_RESULT_INDEX;
				} elseif ($this->slot > 27 && $this->slot < 41) {
					if ($this->slot < 32) {
						$slot = PlayerInventory::CRAFT_INDEX_0 - $this->slot + 28;
					} else {
						$slot = PlayerInventory::CRAFT_INDEX_0 - $this->slot + 32;
					}
				} elseif($this->slot == 14 || $this->slot == 15) {
					$currentWindowId = $player->getCurrentWindowId();
					if ($currentWindowId != $this->inventoryId) {
						$inventory = $player->getCurrentWindow();
						switch ($this->slot) {
							case 14:
								$slot = 0;
								break;
							case 15:
								$slot = 1;
								break;
						}
					}
				} else {
					return null;
				}
				break;
			case Protocol120::CONTAINER_ID_OFFHAND:
				$inventory = $player->getInventory();			
				$slot = $inventory->getSize() + 4;
				break;
			case Protocol120::CONTAINER_ID_ARMOR:
				$inventory = $player->getInventory();
				$slot = $inventory->getSize() + $this->slot;
				break;
			case Protocol120::CONTAINER_ID_NONE:
				$currentWindowId = $player->getCurrentWindowId();
				if ($currentWindowId != $this->inventoryId) {
					// enchanting almost 100%
					$inventory = $player->getCurrentWindow();
					switch ($this->action) {
						case self::ACTION_ENCH_ITEM:
							$slot = 0;
							break;
						case self::ACTION_ENCH_LAPIS:
							$slot = 1;
							break;
						default:
							return null;
					}
					break;
				}
				$inventory = $player->getInventory();
				switch ($this->action) {
					case self::ACTION_CRAFT_GET_RESULT:
						$slot = PlayerInventory::CRAFT_RESULT_INDEX;
						break;
					// client send slot 0 for all craft transactions by quick craft, so we need manage it manually
					case self::ACTION_CRAFT_USE:
						if ($this->slot == 0) {
							$item = $inventory->getItem(PlayerInventory::CRAFT_INDEX_0);
							if (!$this->newItem->equals($item) || $item->getCount() < $this->newItem->getCount()) {
								if (!$inventory->isQuickCraftEnabled()) {
									$inventory->setQuickCraftMode(true);
								}
								$slot = $inventory->getNextFreeQuickCraftSlot();
								break;
							}
						}
					default:						
						$slot = PlayerInventory::CRAFT_INDEX_0 - $this->slot;
						break;
				}
				break;
			case Protocol120::CONTAINER_ID_CREATIVE:
				if (!$player->isCreative() || $player->isSpectator()) {
					return null;
				}
				$inventory = $player->getInventory();
				$slot = PlayerInventory::CREATIVE_INDEX;
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
	
	public function isCraftResultTransaction() {
		return $this->inventoryId == Protocol120::CONTAINER_ID_NONE && $this->action == self::ACTION_CRAFT_GET_RESULT || $this->inventoryId == Protocol120::CONTAINER_ID_CURSOR_SELECTED && $this->slot == 50;
	}
}

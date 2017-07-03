<?php

namespace pocketmine\inventory\win10;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;
use pocketmine\inventory\PlayerInventory;

class Win10InvLogic {
	
	const HOTBAR_OFFSET = 10;
	
	const WINDOW_ID_PLAYER_INVENTORY = 0x00;
	const WINDOW_ID_PLAYER_OFFHAND = 0x77;				// 119
	const WINDOW_ID_PLAYER_ARMOR = 0x78;				// 120
	const WINDOW_ID_HOTBAR = 0x7a;					// 122
	
	/** @var PlayerInventoryData[] */
	protected static $playersInventoryData = [];
	
	public static function packetHandler($packet, Player $player) {
		$playerId = $player->getId();
		if (!isset(self::$playersInventoryData[$playerId])) {
			self::$playersInventoryData[$playerId] = new PlayerInventoryData($player);
		}
		switch ($packet->pname()) {
			case 'CONTAINER_SET_SLOT_PACKET':
//				var_dump($packet);
				$invData = self::$playersInventoryData[$playerId];
				switch ($packet->windowid) {
					case self::WINDOW_ID_PLAYER_INVENTORY:
						$invData->selfInventoryLogic($packet->slot, $packet->item);
						break;
					case self::WINDOW_ID_PLAYER_ARMOR:
						$invData->armorInventoryLogic($packet->slot, $packet->item);
						break;
					default:
						$invData->otherInventoryLogic($packet->slot, $packet->item);
						break;
				}
				break;
			case 'DROP_ITEM_PACKET':
				$invData = self::$playersInventoryData[$playerId];
				$invData->dropItemPreprocessing();
				break;
			case 'MOB_EQUIPMENT_PACKET':
				if ($packet->windowId == self::WINDOW_ID_PLAYER_OFFHAND) {
					$invData = self::$playersInventoryData[$playerId];
					$invData->armorInventoryLogic(PlayerInventory::OFFHAND_ARMOR_SLOT_ID, $packet->item);
					break;
				}
				$inventory = $player->getInventory();
				$inventory->justSetHeldItemIndex($packet->slot);
				
				$pk = new MobEquipmentPacket();
				$pk->eid = $player->getId();
				$pk->item = $packet->item;
				$pk->slot = $inventory->getHeldItemSlot();
				$pk->selectedSlot = $inventory->getHeldItemIndex();

				$level = $player->getLevel();
				$viewers = $player->getViewers();
				foreach($viewers as $viewer){
					if($level->mayAddPlayerHandItem($player, $viewer)) {
						$viewer->dataPacket($pk);
					}
				}
				break;
			default:
				var_dump('Unknovn packet: ' . $packet->pname());
				break;
		}
	}
	
	public static function playerPickUpItem($player, $item) {
		$playerId = $player->getId();
		if (!isset(self::$playersInventoryData[$playerId])) {
			return;
		}
		self::$playersInventoryData[$playerId]->setPickUpItem($item);
	}
    
    /**
     * 
     * @param Player $player
     */
    public static function removeData($player) {
        $playerId = $player->getId();
        if (isset(self::$playersInventoryData[$playerId]) && 
            self::$playersInventoryData[$playerId]->check($player->getInventory())) {
            
			unset(self::$playersInventoryData[$playerId]);
		}
    }
	
}

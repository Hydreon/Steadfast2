<?php

namespace pocketmine\inventory\win10;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\Player;

class Win10InvLogic {
	
	const HOTBAR_OFFSET = 10;
	
	const WINDOW_ID_PLAYER_INVENTORY = 0x00;
	const WINDOW_ID_PLAYER_ARMOR = 0x78;				// 120
	const WINDOW_ID_HOTBAR = 0x7a;					// 122
	
	/** @var PlayerInventoryData[] */
	protected static $playersInventoryData = [];
	
	public static function packetHandler($packet, Player $player) {
		$playerName = $player->getName();
		if (!isset(self::$playersInventoryData[$playerName])) {
			self::$playersInventoryData[$playerName] = new PlayerInventoryData($player);
		}
		$packetID = $packet::NETWORK_ID;
		switch ($packetID) {
			case Info::CONTAINER_SET_SLOT_PACKET:
//				var_dump($packet);
				$invData = self::$playersInventoryData[$playerName];
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
			case Info::DROP_ITEM_PACKET:
				$invData = self::$playersInventoryData[$playerName];
				$invData->dropItemPreprocessing();
				break;
			case Info::MOB_EQUIPMENT_PACKET:
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
				var_dump('Unknovn packet: ' . dechex($packetID));
				break;
		}
	}
	
}

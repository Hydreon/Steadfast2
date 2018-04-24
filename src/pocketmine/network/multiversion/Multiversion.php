<?php

namespace pocketmine\network\multiversion;

use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerInventory120;
use pocketmine\Player;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventorySlotPacket;

abstract class Multiversion {
	
	/**
	 * 
	 * Create player inventory object base on player protocol
	 * 
	 * @param Player $player
	 * @return PlayerInventory
	 */
	public static function getPlayerInventory($player) {
		switch ($player->protocol) {
			case ProtocolInfo::PROTOCOL_120:
			case ProtocolInfo::PROTOCOL_200:
			case ProtocolInfo::PROTOCOL_220:
			case ProtocolInfo::PROTOCOL_221:
			case ProtocolInfo::PROTOCOL_240:
			case ProtocolInfo::PROTOCOL_260:
//				var_dump('Create 120 inv');
				return new PlayerInventory120($player);
			default:
//				var_dump('Create default inv');
				return new PlayerInventory($player);
		}
	}
	
	/**
	 * Send all container's content
	 * 
	 * @param Player $player
	 * @param integer $windowId
	 * @param Item[] $items
	 */
	public static function sendContainer($player, $windowId, $items) {
		$protocol = $player->getPlayerProtocol();
		if ($protocol >= ProtocolInfo::PROTOCOL_120) {
			$pk = new InventoryContentPacket();
			$pk->inventoryID = $windowId;
			$pk->items = $items;
		} else {
			$pk = new ContainerSetContentPacket();			
			$pk->windowid = $windowId;
			$pk->slots = $items;
			$pk->eid = $player->getId();
		}
		$player->dataPacket($pk);
	}
	
	/**
	 * Send one container's slot
	 * 
	 * @param Player $player
	 * @param integer $windowId
	 * @param Item $item
	 * @param integer $slot
	 */
	public static function sendContainerSlot($player, $windowId, $item, $slot) {
		$protocol = $player->getPlayerProtocol();
		if ($protocol >= ProtocolInfo::PROTOCOL_120) {
			$pk = new InventorySlotPacket();
			$pk->containerId = $windowId;
			$pk->item = $item;
			$pk->slot = $slot;
		} else {
			$pk = new ContainerSetSlotPacket();			
			$pk->windowid = $windowId;
			$pk->item = $item;
			$pk->slot = $slot;
		}
		$player->dataPacket($pk);
	}
	
}

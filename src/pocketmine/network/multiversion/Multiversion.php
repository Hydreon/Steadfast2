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
			case ProtocolInfo::PROTOCOL_271:
			case ProtocolInfo::PROTOCOL_273:
			case ProtocolInfo::PROTOCOL_274:
			case ProtocolInfo::PROTOCOL_280:
			case ProtocolInfo::PROTOCOL_282:
			case ProtocolInfo::PROTOCOL_290:
			case ProtocolInfo::PROTOCOL_310:
			case ProtocolInfo::PROTOCOL_311:
			case ProtocolInfo::PROTOCOL_330:
			case ProtocolInfo::PROTOCOL_331:
			case ProtocolInfo::PROTOCOL_332:
			case ProtocolInfo::PROTOCOL_342:
				return new PlayerInventory120($player);
			default:
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

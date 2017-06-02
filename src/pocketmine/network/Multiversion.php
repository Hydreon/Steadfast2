<?php

namespace pocketmine\network;

use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerInventory120;
use pocketmine\Player;
use pocketmine\network\protocol\Info as ProtocolInfo;

class Multiversion {
	
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
				var_dump('Create 120 inv');
				return new PlayerInventory120($player);
			default:
				var_dump('Create default inv');
				return new PlayerInventory($player);
		}
	}
	
}

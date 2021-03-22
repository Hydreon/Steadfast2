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

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>

use pocketmine\item\Item;

class StartGamePacket extends PEPacket{
	const NETWORK_ID = Info::START_GAME_PACKET;
	const PACKET_NAME = "START_GAME_PACKET";
	
	const BROADCAST_SETTINGS_NO_MULTI_PLAY = 0;
	const BROADCAST_SETTINGS_INVITE_ONLY = 1;
	const BROADCAST_SETTINGS_FRIENDS_ONLY = 2;
	const BROADCAST_SETTINGS_FRIENDS_OF_FRIENDS = 3;
	const BROADCAST_SETTINGS_PUBLIC = 4;

	public $seed;
	public $dimension;
	public $generator = 1;
	public $gamemode;
	public $eid;
	public $spawnX;
	public $spawnY;
	public $spawnZ;
	public $x;
	public $y;
	public $z;	
	public $stringClientVersion;
	public static $defaultRules = [
		['name' => 'naturalregeneration', 'type' => 1, 'value' => 0],
//		['name' => 'showcoordinates', 'type' => 1, 'value' => 1]
	];
	public $multiplayerCorrelationId;

	static public $itemsList = [];

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putEntityUniqueId($this->eid);
		$this->putEntityRuntimeId($this->eid);
		$this->putSignedVarInt($this->gamemode);	// Entity gamemode
		$this->putLFloat($this->x); // default position (4)
		$this->putLFloat($this->y); // (4)
		$this->putLFloat($this->z); // (4)
		
		$this->putLFloat(0);
		$this->putLFloat(0);
		
		// Level settings
		
		$this->putSignedVarInt($this->seed);
		
		$this->putShort(0); //SpawnSettingsType

		$this->putString(''); //User Difined Biome type
			
		$this->putSignedVarInt($this->dimension);
		
		$this->putSignedVarInt($this->generator);
		
		$this->putSignedVarInt($this->gamemode);
		
		$this->putSignedVarInt(1); // Difficulty
		
		// default spawn 3x VarInt
		$this->putSignedVarInt($this->spawnX);
		$this->putVarInt($this->spawnY);
		$this->putSignedVarInt($this->spawnZ);

		$this->putByte(1); // hasAchievementsDisabled
		
		$this->putSignedVarInt(0); // DayCycleStopTyme 1x VarInt

		$this->putSignedVarInt(0); //edu edition offer
				
		$this->putByte(0); //edu mode
		
		$this->putString(''); //edu product id
		
		$this->putLFloat(0); //rain level

		$this->putLFloat(0); //lightning level

		$this->putByte(0); // has confirmed platform Locked Content
		
		$this->putByte(1); // is multiplayer game
		$this->putByte(1); // Broadcast to LAN?
		$this->putSignedVarInt(self::BROADCAST_SETTINGS_FRIENDS_OF_FRIENDS); // XBox Live Broadcast setting
		$this->putSignedVarInt(self::BROADCAST_SETTINGS_FRIENDS_OF_FRIENDS); // Platform Broadcast setting
				
		$this->putByte(1);	// commands enabled
		
		$this->putByte(0); // isTexturepacksRequired 1x Byte		
		$this->putVarInt(count(self::$defaultRules)); // rules count
		foreach (self::$defaultRules as $rule) {
			$this->putString($rule['name']);
			$this->putVarInt($rule['type']);
			switch ($rule['type']) {
				case 1:
					$this->putByte($rule['value']);
					break;
				case 2:
					$this->putSignedVarInt($rule['value']);
					break;
				case 3:
					$this->putLFloat($rule['value']);
					break;
			}
		}
		$this->putLInt(0); //Experiments
		$this->putBool(0); //hasPreviouslyUsedExperiments

        $this->putByte(0); // is bonus chest enabled
		$this->putByte(0); // is start with map enabled
		$this->putSignedVarInt(0); // permission level
		$this->putLInt(0); // server chunk tick range
   		$this->putByte(0); // Has locked behavior pack?
		$this->putByte(0); // Has locked resource pack?
		$this->putByte(0); // Is from locked template?
		$this->putByte(0); // Use Msa Gamertags Only?
		$this->putByte(0); // Is From World Template?
		$this->putByte(1); // Is World Template Option Locked?
		$this->putByte(0); // Only spawn v1 villagers
		$this->putString(''); // Vanila version
				
					
		$this->putLInt(16); //Limited word width
		$this->putLInt(16); //Limited word depth			
		$this->putByte(0); //Nether type
		$this->putByte(0); //exp gameplay
		
		
		// level settings end
		$this->putString('3138ee93-4a4a-479b-8dca-65ca5399e075'); // level id (random UUID)
		$this->putString(''); // level name
		$this->putString(''); // template pack id
		$this->putByte(0); // is trial?
		if ($playerProtocol >= Info::PROTOCOL_419) {
			$this->putVarInt(0);
			if ($playerProtocol >= Info::PROTOCOL_428) {	
				$this->putSignedVarInt(0);
				$this->putByte(0);
			}
		} else {
			$this->putByte(0); // is server authoritative over movement
		}
		

		$this->putLong(0); // current level time
		if ($playerProtocol >= Info::PROTOCOL_419) {
			$this->putVarInt(0);
		} 
		$this->putSignedVarInt(0); // enchantment seed  ????????

		if ($playerProtocol < Info::PROTOCOL_419) {
			$this->put(self::getBlockPalletData($playerProtocol));
		}

        
		if ($playerProtocol >= Info::PROTOCOL_419) {
			$itemsData = self::getItemsList();
			$this->putVarInt(count($itemsData));
			foreach ($itemsData as $name => $id) {
				$this->putString($name);
				$this->putLShort($id);
				$this->putByte(0);
			}
		} else {
			$this->putVarInt(0); // item list size
		}
		
		$this->putString($this->multiplayerCorrelationId); //multiplayerCorrelationId
		$this->putByte(0); // Whether the new item stack net manager is enabled for server authoritative inventory
	}

	static protected function getItemsList() {
		if (!empty(self::$itemsList)) {
			return self::$itemsList;
		} else {
			$path = __DIR__ . "/data/Items.json";
			self::$itemsList = json_decode(file_get_contents($path), true);
			return self::$itemsList;
		}

	}
}

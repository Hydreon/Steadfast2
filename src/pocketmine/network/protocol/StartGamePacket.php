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
		['name' => 'naturalRegeneration', 'type' => 1, 'value' => 0],
//		['name' => 'showcoordinates', 'type' => 1, 'value' => 1]
	];
	public $multiplayerCorrelationId;
	public function decode($playerProtocol){
	}
	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid); //EntityUniqueID
		$this->putVarInt($this->eid); //EntityUniqueID
		$this->putSignedVarInt($this->gamemode);	// Entity gamemode
		$this->putLFloat($this->x); // default position (4)
		$this->putLFloat($this->y); // (4)
		$this->putLFloat($this->z); // (4)
		
		$this->putLFloat(0);
		$this->putLFloat(0);
		
		// Level settings
		
		$this->putSignedVarInt($this->seed);
		
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
		
		$this->putByte(0); //edu mode
		
		if ($playerProtocol >= Info::PROTOCOL_260 && $this->stringClientVersion != '1.2.20.1') {
			$this->putByte(0); // Are education features enabled?
		}
		$this->putLFloat(0); //rain level
		$this->putLFloat(0); //lightning level
		if ($playerProtocol >= Info::PROTOCOL_332) {
			$this->putByte(0); // ???
		}
		
		$this->putByte(1); // is multiplayer game
		$this->putByte(1); // Broadcast to LAN?
		if ($playerProtocol >= Info::PROTOCOL_330) {
			$this->putSignedVarInt(self::BROADCAST_SETTINGS_FRIENDS_OF_FRIENDS); // XBox Live Broadcast setting
			$this->putSignedVarInt(self::BROADCAST_SETTINGS_FRIENDS_OF_FRIENDS); // Platform Broadcast setting
		} else {
			$this->putByte(1); // Broadcast to XBL?
		}
				
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
		$this->putByte(0); // is bonus chest enabled
		$this->putByte(0); // is start with map enabled
		if ($playerProtocol < Info::PROTOCOL_330) {
			$this->putByte(0); // has trust players enabled
		}
		$this->putSignedVarInt(1); // permission level
		if ($playerProtocol < Info::PROTOCOL_330) {
			$this->putSignedVarInt(4); // game publish setting
		}
		$this->putLInt(0); // server chunk tick range
		if ($playerProtocol < Info::PROTOCOL_330) {
			$this->putByte(0); // can platform broadcast
			$this->putSignedVarInt(0); // Broadcast mode
			$this->putByte(0); // XBL Broadcast intent
		}
		if ($playerProtocol >= Info::PROTOCOL_260 && $this->stringClientVersion != '1.2.20.1') {
			$this->putByte(0); // Has locked behavior pack?
			$this->putByte(0); // Has locked resource pack?
			$this->putByte(0); // Is from locked template?
			if ($playerProtocol >= Info::PROTOCOL_290) {
				$this->putByte(0); // Use Msa Gamertags Only?
			}
			if ($playerProtocol >= Info::PROTOCOL_311) {
				$this->putByte(0); // Is From World Template?
				$this->putByte(0); // Is World Template Option Locked?
			}
			if ($playerProtocol >= Info::PROTOCOL_361) {
				$this->putByte(1); // Only spawn v1 villagers
			}			
			if ($playerProtocol >= Info::PROTOCOL_370) {
				$this->putString(''); // Vanila version
			}
			if ($playerProtocol >= Info::PROTOCOL_386) {
				$this->putByte(0); // unknown
				$this->putByte(1); // unknown
				$this->putLFloat(0); // unknown
			}
		}
		// level settings end
		$this->putString('3138ee93-4a4a-479b-8dca-65ca5399e075'); // level id (random UUID)
		$this->putString(''); // level name
		$this->putString(''); // template pack id
		$this->putByte(0); // is trial?
		$this->putLong(0); // current level time
		$this->putSignedVarInt(0); // enchantment seed
		if ($playerProtocol >= Info::PROTOCOL_280) {
			$this->put(self::getBlockPalletData($playerProtocol));
		}
		if ($playerProtocol >= Info::PROTOCOL_360) {
			$this->putVarInt(0); // item list size
		}
		if ($playerProtocol >= Info::PROTOCOL_282) {
			$this->putString($this->multiplayerCorrelationId);
		}
	}
}

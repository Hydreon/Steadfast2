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

class AdventureSettingsPacket extends PEPacket{
	const NETWORK_ID = Info::ADVENTURE_SETTINGS_PACKET;
	const PACKET_NAME = "ADVENTURE_SETTINGS_PACKET";

	const ACTION_FLAG_PROHIBIT_ALL = 0;
	const ACTION_FLAG_BUILD_AND_MINE = 1;
	const ACTION_FLAG_DOORS_AND_SWITCHES = 2;
	const ACTION_FLAG_OPEN_CONTAINERS = 4;
	const ACTION_FLAG_ATTACK_PLAYERS = 8;
	const ACTION_FLAG_ATTACK_MOBS = 16;
	const ACTION_FLAG_OP = 32;
	const ACTION_FLAG_TELEPORT = 64;
	const ACTION_FLAG_DEFAULT_LEVEL_PERMISSIONS = 128;
	const ACTION_FLAG_ALLOW_ALL = 511;
	
	const PERMISSION_LEVEL_VISITOR = 0;
	const PERMISSION_LEVEL_MEMBER = 1;
	const PERMISSION_LEVEL_OPERATOR = 2;
	const PERMISSION_LEVEL_CUSTOM = 3;
	
	public $flags = 0;
	public $actionPermissions = self::ACTION_FLAG_DEFAULT_LEVEL_PERMISSIONS;
	public $permissionLevel = self::PERMISSION_LEVEL_MEMBER;
	public $customStoredPermissions = 0;
	public $userId = 0;
	
	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
        $this->flags = $this->getVarInt();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->flags);
		$this->putVarInt(0);
		switch ($playerProtocol) {
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_260:
				$this->putVarInt($this->actionPermissions);
				$this->putVarInt($this->permissionLevel);
				$this->putVarInt($this->customStoredPermissions);
				// we should put eid as long but in signed varint format
				// maybe i'm wrong but it works
				if ($this->userId & 1) { // userId is odd
					$this->putLLong(-1 * (($this->userId + 1) >> 1));
				} else { // userId is even
					$this->putLLong($this->userId >> 1);
				}
				break;
		}
	}

}

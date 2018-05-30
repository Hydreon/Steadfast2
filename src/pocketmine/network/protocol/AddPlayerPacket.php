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

#ifndef COMPILE
use pocketmine\utils\Binary;
use pocketmine\entity\Entity;

#endif

class AddPlayerPacket extends PEPacket{
	const NETWORK_ID = Info::ADD_PLAYER_PACKET;
	const PACKET_NAME = "ADD_PLAYER_PACKET";

	public $uuid;
	public $username;
	public $eid;
	public $x;
	public $y;
	public $z;
	public $speedX;
	public $speedY;
	public $speedZ;
	public $pitch;
	public $yaw;
	public $item;
	public $metadata;
	public $links = [];
	public $flags = 0;
	public $commandPermission = 0;
	public $actionPermissions = AdventureSettingsPacket::ACTION_FLAG_DEFAULT_LEVEL_PERMISSIONS;
	public $permissionLevel = AdventureSettingsPacket::PERMISSION_LEVEL_MEMBER;
	public $storedCustomPermissions = 0;

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putUUID($this->uuid);
		//hack for display name 200+ protocol
		if ($playerProtocol >= Info::PROTOCOL_200 && !empty($this->metadata[Entity::DATA_NAMETAG])) {
			$this->putString($this->metadata[Entity::DATA_NAMETAG][1]);
		} else {
			$this->putString($this->username);
		}
		if ($playerProtocol >= Info::PROTOCOL_200) {
			$this->putString(""); // third party name
			$this->putSignedVarInt(0); // platform id
		}
		$this->putVarInt($this->eid);
		$this->putVarInt($this->eid);
		if ($playerProtocol >= Info::PROTOCOL_200) {
			$this->putString(""); // platform chat id
		}
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putLFloat($this->speedX);
		$this->putLFloat($this->speedY);
		$this->putLFloat($this->speedZ);
		$this->putLFloat($this->pitch);
		$this->putLFloat($this->yaw);
		$this->putLFloat($this->yaw);//TODO headrot	
		$this->putSignedVarInt(0);
//		$this->putSlot($this->item, $playerProtocol);

		$meta = Binary::writeMetadata($this->metadata, $playerProtocol);
		$this->put($meta);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putVarInt($this->flags);
			$this->putVarInt($this->commandPermission);
			$this->putVarInt($this->actionPermissions);
			$this->putVarInt($this->permissionLevel);
			$this->putVarInt($this->storedCustomPermissions);
			// we should put eid as long but in signed varint format
			// maybe i'm wrong but it works
			if ($this->eid & 1) { // userId is odd
				$this->putLLong(-1 * (($this->eid + 1) >> 1));
			} else { // userId is even
				$this->putLLong($this->eid >> 1);
			}
			$this->putVarInt(count($this->links));
			foreach ($this->links as $link) {
				$this->putVarInt($link['from']);
				$this->putVarInt($link['to']);
				$this->putByte($link['type']);
				$this->putByte(0);
			}
		}
	}

}
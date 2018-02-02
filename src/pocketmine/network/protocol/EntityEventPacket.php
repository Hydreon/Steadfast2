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


class EntityEventPacket extends PEPacket{
	const NETWORK_ID = Info::ENTITY_EVENT_PACKET;
	const PACKET_NAME = "ENTITY_EVENT_PACKET";

	const HURT_ANIMATION = 2;
	const DEATH_ANIMATION = 3;
	const START_ATACKING = 4;

	const TAME_FAIL = 6;
	const TAME_SUCCESS = 7;
	const SHAKE_WET = 8;
	const USE_ITEM = 9;
	const EAT_GRASS_ANIMATION = 10;
	const FISH_HOOK_BUBBLE = 11;
	const FISH_HOOK_POSITION = 12;
	const FISH_HOOK_HOOK = 13;
	const FISH_HOOK_TEASE = 14;
	const SQUID_INK_CLOUD = 15;
	const AMBIENT_SOUND = 17;
	const RESPAWN = 18;
	const ENCHANT = 34;
	const FEED = 57;

	//TODO add new events

	public $eid;
	public $event;
	public $theThing;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		$this->event = $this->getByte();
		$this->theThing = $this->getSignedVarInt();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putByte($this->event);
		/** @todo do it right */
		$this->putSignedVarInt(0); // event data
	}

}

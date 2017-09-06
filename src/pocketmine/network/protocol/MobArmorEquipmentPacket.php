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


class MobArmorEquipmentPacket extends PEPacket{
	const NETWORK_ID = Info::MOB_ARMOR_EQUIPMENT_PACKET;
	const PACKET_NAME = "MOB_ARMOR_EQUIPMENT_PACKET";

	public $eid;
	public $slots = [];

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		$this->slots[0] = $this->getSlot($playerProtocol);
		$this->slots[1] = $this->getSlot($playerProtocol);
		$this->slots[2] = $this->getSlot($playerProtocol);
		$this->slots[3] = $this->getSlot($playerProtocol);
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putSlot($this->slots[0], $playerProtocol);
		$this->putSlot($this->slots[1], $playerProtocol);
		$this->putSlot($this->slots[2], $playerProtocol);
		$this->putSlot($this->slots[3], $playerProtocol);
	}

}

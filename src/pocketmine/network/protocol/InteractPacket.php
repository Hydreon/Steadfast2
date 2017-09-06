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


class InteractPacket extends PEPacket{
	const NETWORK_ID = Info::INTERACT_PACKET;
	const PACKET_NAME = "INTERACT_PACKET";
	
	const ACTION_DAMAGE = 2;
	const ACTION_SEE = 4;

	public $action;
	public $eid;
	public $target;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->action = $this->getByte();
		$this->target = $this->getVarInt();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->action);
		$this->putVarInt($this->target);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			/** @todo do it right */
			if ($this->action == self::ACTION_SEE) {
				$this->putLFloat(0); // position X
				$this->putLFloat(0); // position Y
				$this->putLFloat(0); // position Z
			}
		}
	}

}

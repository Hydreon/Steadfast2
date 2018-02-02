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


class AnimatePacket extends PEPacket{
	const NETWORK_ID = Info::ANIMATE_PACKET;
	const PACKET_NAME = "ANIMATE_PACKET";

	const ACTION_NO_ACTION = 0;
	const ACTION_SWING = 1;
	const ACTION_WAKE_UP = 3;
	const ACTION_CRITICAL_HIT = 4;
	const ACTION_MAGIC_CRITICAL_HIT = 5;
	const ACTION_ROW_RIGHT = 128;	// for boat?
	const ACTION_ROW_LEFT = 129;	// for boat?
	
	public $action;
	public $eid;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->action = $this->getSignedVarInt();
		} else {
			$this->action = $this->getVarInt();
		}
	
		$this->eid = $this->getVarInt();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putSignedVarInt($this->action);
		} else {
			$this->putVarInt($this->action);
		}
		$this->putVarInt($this->eid);
		switch ($this->action) {
			case self::ACTION_ROW_RIGHT:
			case self::ACTION_ROW_LEFT:
				/** @todo do it right */
				$this->putLFloat(0);
		}
	}

}

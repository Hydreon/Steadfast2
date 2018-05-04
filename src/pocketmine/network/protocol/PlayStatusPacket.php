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


class PlayStatusPacket extends PEPacket{
	const NETWORK_ID = Info::PLAY_STATUS_PACKET;
	const PACKET_NAME = "PLAY_STATUS_PACKET";
	
	const LOGIN_SUCCESS = 0;
	const LOGIN_FAILED_CLIENT = 1;
	const LOGIN_FAILED_SERVER = 2;
	const PLAYER_SPAWN = 3;
	const EDU_NO_ACCESS = 4;
	const EDU_LEVEL_TYPE = 5;
	
	public $status;
	
	public function reset($playerProtocol = 0) {
		if (isset(self::$packetsIds[$playerProtocol])) {
			$this->buffer = chr(self::$packetsIds[$playerProtocol][$this::PACKET_NAME]);
		} else {
			$this->buffer = chr(Info::DISCONNECT_PACKET);
		}
		$this->offset = 0;
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte($this->senderSubClientID);
			$this->putByte($this->targetSubClientID);
			$this->offset = 2;
		}
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putInt($this->status);
	}

}

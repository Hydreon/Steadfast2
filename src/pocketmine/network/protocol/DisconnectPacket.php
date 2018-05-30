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


class DisconnectPacket extends PEPacket {
	
	const NETWORK_ID = Info::DISCONNECT_PACKET;
	const PACKET_NAME = "DISCONNECT_PACKET";

	public $hideDisconnectReason = false;
	public $message = '';

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
	
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->hideDisconnectReason = $this->getByte();
		if ($this->hideDisconnectReason == false) {
			$this->message = $this->getString();
		}
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->hideDisconnectReason);
		if ($this->hideDisconnectReason == false) {
			$this->putString($this->message);
		}
	}

}

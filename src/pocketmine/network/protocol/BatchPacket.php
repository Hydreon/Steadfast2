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


class BatchPacket extends PEPacket{
	const NETWORK_ID = Info::BATCH_PACKET;
	const PACKET_NAME = "BATCH_PACKET";

	public $payload;
	public $is110 = false;

	public function decode($playerProtocol) {
		if ($this->is110) {
			$playerProtocol = Info::PROTOCOL_110;
		}
		switch ($playerProtocol) {
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_110:
				$this->payload = $this->get(true);
				break;
			default:
				$this->payload = $this->getString();
				break;
		}
	}

	public function encode($playerProtocol) {
		switch ($playerProtocol) {
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_110:
				$this->buffer = $this->payload;
				break;
			default:
				$this->reset($playerProtocol);
				$this->putString($this->payload);
				break;
		}
	}

}
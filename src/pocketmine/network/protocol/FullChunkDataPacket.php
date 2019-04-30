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


class FullChunkDataPacket extends PEPacket{
	const NETWORK_ID = Info::FULL_CHUNK_DATA_PACKET;
	const PACKET_NAME = "FULL_CHUNK_DATA_PACKET";
	
	public $chunkX;
	public $chunkZ;
	public $data;

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->chunkX);
		$this->putSignedVarInt($this->chunkZ);
		if ($playerProtocol >= Info::PROTOCOL_360) {
			$this->putVarInt(ord($this->data[0]));
			$this->putByte(0);
			$this->putString(substr($this->data, 1));
		} else {
			$this->putString($this->data);
		}
	}

}

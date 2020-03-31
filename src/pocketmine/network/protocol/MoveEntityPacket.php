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


class MoveEntityPacket extends PEPacket{
	const NETWORK_ID = Info::MOVE_ENTITY_PACKET;
	const PACKET_NAME = "MOVE_ENTITY_PACKET";


	// eid, x, y, z, yaw, pitch
	/** @var array[] */
	public $entities = [];

	public function clean(){
		$this->entities = [];
		return parent::clean();
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$data = array_shift($this->entities);
		if ($playerProtocol >= Info::PROTOCOL_273) {
			$this->putVarInt($data[0]); //eid
			$flags = 0;
			$flags |= 1 << 7;// is on ground?
//			$flags |= 0 << 6;// has teleported?
			if ($playerProtocol >= Info::PROTOCOL_274) {
				$this->putByte($flags);
			} else {
				$this->putLShort($flags);
			}
			$this->putLFloat($data[1]); //x
			$this->putLFloat($data[2]); //y
			$this->putLFloat($data[3]); //z
			$this->putByte($data[6] * 0.71111); //pitch
			$this->putByte($data[4] * 0.71111); //yaw
			$this->putByte($data[5] * 0.71111); //headYaw	
		} else {
			$this->putVarInt($data[0]); //eid
			$this->putLFloat($data[1]); //x
			$this->putLFloat($data[2]); //y
			$this->putLFloat($data[3]); //z
			$this->putByte($data[6] * 0.71111); //pitch
			$this->putByte($data[5] * 0.71111); //headYaw
			$this->putByte($data[4] * 0.71111); //yaw
			$this->putByte(true); // is on ground?
			$this->putByte(false); // has teleported?
		}
	}
}
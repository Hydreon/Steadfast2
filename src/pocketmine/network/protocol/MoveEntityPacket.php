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
	
	public function __construct() {
		parent::__construct("", 0);
	}

	public function clean(){
		$this->entities = [];
		return parent::clean();
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		foreach($this->entities as $d){
			$this->putVarInt($d[0]); //eid
			$this->putLFloat($d[1]); //x
			$this->putLFloat($d[2]); //y
			$this->putLFloat($d[3]); //z
			$this->putByte($d[6] * 0.71111); //pitch
			$this->putByte($d[5] * 0.71111); //headYaw
			$this->putByte($d[4] * 0.71111); //yaw
			/** @todo do it right */
			$this->putByte(true); // is on ground?
			$this->putByte(false); // has teleported?
		}
	}
}
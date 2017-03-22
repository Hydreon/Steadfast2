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


class SetEntityMotionPacket extends PEPacket{
	const NETWORK_ID = Info::SET_ENTITY_MOTION_PACKET;
	const PACKET_NAME = "SET_ENTITY_MOTION_PACKET";


	// eid, motX, motY, motZ
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
			$this->putLFloat($d[1]); //motX
			$this->putLFloat($d[2]); //motY
			$this->putLFloat($d[3]); //motZ
		}
	}

}

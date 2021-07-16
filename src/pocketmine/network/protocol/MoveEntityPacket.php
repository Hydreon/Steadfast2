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
	public $eid;
	public $x;
	public $y;
	public $z;
	public $pitch;
	public $yaw;
	public $headYaw;
	
	public function clean(){
		$this->entities = [];
		return parent::clean();
	}

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->eid = $this->getVarInt();
		$this->getByte();
		$this->x = $this->getLFloat();
		$this->y = $this->getLFloat();
		$this->z = $this->getLFloat();
		$this->pitch = $this->getByte() * 1.40625;
		$this->headYaw = $this->getByte() * 1.40625;
		$this->yaw = $this->getByte() * 1.40625;
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$data = array_shift($this->entities);
		$this->putEntityRuntimeId($data[0]); //eid
		$flags = 0;
		$flags |= 1 << 7;// is on ground?
//		$flags |= 0 << 6;// has teleported?
		$this->putByte($flags);
		$this->putLFloat($data[1]); //x
		$this->putLFloat($data[2]); //y
		$this->putLFloat($data[3]); //z
		$this->putByte($data[6] * 0.71111); //pitch
		$this->putByte($data[4] * 0.71111); //yaw
		$this->putByte($data[5] * 0.71111); //headYaw
	}
}

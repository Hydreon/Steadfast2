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


class PlayerInputPacket extends PEPacket{
	const NETWORK_ID = Info::PLAYER_INPUT_PACKET;
	const PACKET_NAME = "PLAYER_INPUT_PACKET";

	public $forward;
	public $sideway;
	public $jump;
	public $sneak;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->forward = $this->getLFloat(); //лево(1) право(-1)
		$this->sideway = $this->getLFloat(); //вперед(1) назад(-1)
	//	var_dump($forward . " " . $sideway);
		$this->jump = $this->getByte();
		$this->sneak = $this->getByte();
	}

	public function encode($playerProtocol){

	}

}

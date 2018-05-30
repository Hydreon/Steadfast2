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

class CommandStepPacket extends PEPacket {

	const NETWORK_ID = Info::COMMAND_STEP_PACKET;
	const PACKET_NAME = "COMMAND_STEP_PACKET";

	public $name;
	public $overload;
	public $outputFormat;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->name = $this->getString();
		$this->overload = $this->getString();

		$this->getVarInt();
		$this->getVarInt();
		$this->getByte();
		$this->getVarInt();

		$this->outputFormat = $this->getString();
	}

	public function encode($playerProtocol) {
		
	}

}

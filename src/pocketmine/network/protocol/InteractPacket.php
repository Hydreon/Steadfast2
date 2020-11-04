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


class InteractPacket extends PEPacket{
	const NETWORK_ID = Info::INTERACT_PACKET;
	const PACKET_NAME = "INTERACT_PACKET";


	/**
	Invalid = 0
	StopRiding = 3
	InteractUpdate = 4
	NpcOpen = 5
	OpenInventory = 6
	 */

	const ACTION_INVALID = 0;
	const ACTION_DAMAGE = 2;
	const ACTION_STOP_RIDING = 3;
	const ACTION_INTERACT_UPDATE = 4;
	const ACTION_OPEN_NPC = 4;
	const ACTION_OPEN_INVENTORY = 6;

	public $action;
	public $eid;
	public $target;
	/** @var float */
	public $x;
	/** @var float */
	public $y;
	/** @var float */
	public $z;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->action = $this->getByte();
		$this->target = $this->getVarInt(); // Runtime ID

		if ($this->action == self::ACTION_INTERACT_UPDATE || $this->action == self::ACTION_STOP_RIDING) {
			$this->getLFloat();
			$this->getLFloat();
			$this->getLFloat();
		}
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->action);
		$this->putVarInt($this->target);
		if ($this->action == self::ACTION_INTERACT_UPDATE || $this->action == self::ACTION_STOP_RIDING) {
			$this->putLFloat($this->x);
			$this->putLFloat($this->y);
			$this->putLFloat($this->z);
		}
	}

}

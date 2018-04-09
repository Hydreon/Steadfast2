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

use pocketmine\item\Item;

class ContainerSetSlotPacket extends PEPacket{
	const NETWORK_ID = Info::CONTAINER_SET_SLOT_PACKET;

	public $windowid;
	public $slot;
	public $hotbarSlot = 0;
	/** @var Item */
	public $item;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->windowid = $this->getByte();
		$this->slot = $this->getSignedVarInt();
		$this->hotbarSlot = $this->getSignedVarInt();
		$this->item = $this->getSlot($playerProtocol);
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->windowid);
		$this->putSignedVarInt($this->slot);
		$this->putSignedVarInt($this->hotbarSlot);
		$this->putSlot($this->item, $playerProtocol);
	}
}

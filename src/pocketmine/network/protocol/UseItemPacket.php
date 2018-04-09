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


class UseItemPacket extends PEPacket{
	const NETWORK_ID = Info::USE_ITEM_PACKET;

	public $x;
	public $y;
	public $z;
	public $face;
	public $item;
	public $fx;
	public $fy;
	public $fz;
	public $posX;
	public $posY;
	public $posZ;
	public $hotbarSlot;
	public $interactBlockId;

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->x = $this->getSignedVarInt();
		$this->y = $this->getVarInt();
		$this->z = $this->getSignedVarInt();
		$this->interactBlockId =  $this->getVarInt();
		$this->face = $this->getSignedVarInt();
		$this->fx = $this->getLFloat();
		$this->fy = $this->getLFloat();
		$this->fz = $this->getLFloat();
		$this->posX = $this->getLFloat();
		$this->posY = $this->getLFloat();
		$this->posZ = $this->getLFloat();
		$this->hotbarSlot = $this->getSignedVarInt();
		$this->item = $this->getSlot($playerProtocol);
	}

	public function encode($playerProtocol){

	}
}

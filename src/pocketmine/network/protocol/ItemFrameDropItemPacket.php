<?php

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>

class ItemFrameDropItemPacket extends PEPacket{

	const NETWORK_ID = Info::ITEM_FRAME_DROP_ITEM_PACKET;
	const PACKET_NAME = "ITEM_FRAME_DROP_ITEM_PACKET";

	public $x;
	public $y;
	public $z;

	public function decode($playerProtocol){
        //$this->getHeader($playerProtocol);
		$this->getBlockPosition($this->x, $this->y, $this->z);
		//$this->item = $this->getSlot($playerProtocol);
	}

	public function encode($playerProtocol){
        //$this->reset($playerProtocol);
        //$this->putBlockCoords($this->x, $this->y, $this->z);
        //$this->setS
	}
}

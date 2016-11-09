<?php


namespace proxy\network;

use raklib\protocol\EncapsulatedPacket;

class CachedEncapsulatedPacket extends EncapsulatedPacket{

	private $internalData = null;

	public function toBinary($internal = false){
		return $this->internalData === null ? ($this->internalData = parent::toBinary($internal)) : $this->internalData;
	}
}
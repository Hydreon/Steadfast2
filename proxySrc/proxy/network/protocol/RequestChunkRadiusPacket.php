<?php


namespace proxy\network\protocol;



class RequestChunkRadiusPacket extends DataPacket{
	const NETWORK_ID = Info::REQUEST_CHUNK_RADIUS_PACKET;

	public $radius;

	public function decode(){
		$this->radius = $this->getInt();
	}

	public function encode(){
		$this->reset();
		$this->putInt($this->radius);
	}

}
<?php

namespace proxy\network\protocol;

class BatchPacket extends DataPacket{
	const NETWORK_ID = Info::BATCH_PACKET;

	public $payload;

	public function decode(){
		$size = $this->getInt();
		$this->payload = $this->get($size);
	}

	public function encode(){
		$this->reset();
		$this->putInt(strlen($this->payload));
		$this->put($this->payload);
	}

}
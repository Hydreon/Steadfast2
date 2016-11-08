<?php

namespace proxy\network\protocol;


class DisconnectPacket extends DataPacket{
	const NETWORK_ID = Info::DISCONNECT_PACKET;

	public $message;

	public function decode(){
		$this->message = $this->getString();
	}

	public function encode(){
		$this->reset();
		$this->putString($this->message);
	}

}

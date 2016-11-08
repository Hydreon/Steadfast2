<?php


namespace proxy\network\protocol;


class PlayStatusPacket extends DataPacket{
	const NETWORK_ID = Info::PLAY_STATUS_PACKET;
	
	const LOGIN_SUCCESS = 0;
	const LOGIN_FAILED_CLIENT = 1;
	const LOGIN_FAILED_SERVER = 2;
	const PLAYER_SPAWN = 3;

	public $status;

	public function decode(){

	}

	public function encode(){
		$this->reset();
		$this->putInt($this->status);
	}

}

<?php

namespace pocketmine\network\protocol;

use pocketmine\utils\BinaryStream;

class PlayStatusPacket extends PEPacket{
	const NETWORK_ID = Info::PLAY_STATUS_PACKET;
	const PACKET_NAME = "PLAY_STATUS_PACKET";
	
	const LOGIN_SUCCESS = 0;
	const LOGIN_FAILED_CLIENT = 1;
	const LOGIN_FAILED_SERVER = 2;
	const PLAYER_SPAWN = 3;
	const EDU_NO_ACCESS = 4;
	const EDU_LEVEL_TYPE = 5;
	
	public $status;
	
	public function reset($playerProtocol = 0) {
		if (isset(self::$packetsIds[$playerProtocol])) {
			parent::reset($playerProtocol);
		} else {
			BinaryStream::reset();
			$this->putByte(Info::PLAY_STATUS_PACKET);
		}
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putInt($this->status);
	}

}

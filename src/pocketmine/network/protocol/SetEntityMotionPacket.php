<?php

namespace pocketmine\network\protocol;

class SetEntityMotionPacket extends PEPacket{
	const NETWORK_ID = Info::SET_ENTITY_MOTION_PACKET;
	const PACKET_NAME = "SET_ENTITY_MOTION_PACKET";


	// eid, motX, motY, motZ
	/** @var array[] */
	public $entities = [];

	public function clean(){
		$this->entities = [];
		return parent::clean();
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		foreach($this->entities as $d){
			$this->putVarInt($d[0]); //eid
			$this->putLFloat($d[1]); //motX
			$this->putLFloat($d[2]); //motY
			$this->putLFloat($d[3]); //motZ
		}
	}

}

<?php

namespace pocketmine\network\protocol;

class UpdateBlockPacket extends PEPacket{
	const NETWORK_ID = Info::UPDATE_BLOCK_PACKET;
	const PACKET_NAME = "UPDATE_BLOCK_PACKET";

	const FLAG_NONE      = 0b0000;
	const FLAG_NEIGHBORS = 0b0001;
	const FLAG_NETWORK   = 0b0010;
	const FLAG_NOGRAPHIC = 0b0100;
	const FLAG_PRIORITY  = 0b1000;

	const FLAG_ALL = (self::FLAG_NEIGHBORS | self::FLAG_NETWORK);
	const FLAG_ALL_PRIORITY = (self::FLAG_ALL | self::FLAG_PRIORITY);

	public $records = []; //x, z, y, blockId, blockData, flags
	
	public function decode($playerProtocol){
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		foreach($this->records as $r){
			$this->putSignedVarInt($r[0]);			
			$this->putVarInt($r[2]);
			$this->putSignedVarInt($r[1]);
			if ($playerProtocol >= Info::PROTOCOL_220) {
				$runtimeId = self::getBlockRuntimeID($r[3], $r[4], $playerProtocol);				
				$this->putVarInt($runtimeId);
				$this->putVarInt($r[5]);
				$this->putVarInt(0);
			} else {
				$this->putVarInt($r[3]);
				$this->putVarInt(($r[5] << 4) | $r[4]);
			}
		}
	}

}

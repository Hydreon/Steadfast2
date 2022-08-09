<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class NetworkChunkPublisherUpdatePacket extends PEPacket {
	
	const NETWORK_ID = Info331::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET;
	const PACKET_NAME = "NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET";
	
	public $x;
	public $y;
	public $z;
	public $radius;
	
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->x = $this->getSignedVarInt();
		$this->y = $this->getSignedVarInt();
		$this->z = $this->getSignedVarInt();
		$this->radius = $this->getVarInt();
		if($playerProtocol >= Info::PROTOCOL_544) {
			for ($i = 0, $count = $this->getLInt(); $i < $count; $i++) {
				$this->getSignedVarInt();
				$this->getSignedVarInt();
			}
		}
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->x);
		$this->putSignedVarInt($this->y);
		$this->putSignedVarInt($this->z);
		$this->putVarInt($this->radius);
		if($playerProtocol >= Info::PROTOCOL_544) {
			$this->putLInt(0);
		}
	}

}

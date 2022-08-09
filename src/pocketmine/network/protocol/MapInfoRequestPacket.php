<?php

namespace pocketmine\network\protocol;

class MapInfoRequestPacket extends PEPacket {

	const NETWORK_ID = Info::MAP_INFO_REQUEST_PACKET;
	const PACKET_NAME = "MAP_INFO_REQUEST_PACKET";
	
	public $mapId;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->mapId = $this->getEntityUniqueId();
		if ($playerProtocol >= Info::PROTOCOL_544) {
			for ($i = 0, $count = $this->getVarInt(); $i < $count; $i++) {
				$this->getLInt();
				$this->getLShort();
			}
		}
	}

	public function encode($playerProtocol) {
		
	}

}

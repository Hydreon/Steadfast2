<?php

namespace pocketmine\network\protocol;

class ResourcePackChunkRequestPacket extends PEPacket {
	
	const NETWORK_ID = Info::RESOURCE_PACK_CHUNK_REQUEST_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_CHUNK_REQUEST_PACKET";
	
	public $resourcePackId = "";
	public $requestChunkIndex = 0;
	
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->resourcePackId = $this->getString();
		$this->requestChunkIndex = $this->getLInt();
	}

	public function encode($playerProtocol) {
		
	}

}

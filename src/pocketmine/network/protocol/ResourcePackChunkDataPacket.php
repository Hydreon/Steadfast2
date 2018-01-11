<?php

namespace pocketmine\network\protocol;

class ResourcePackChunkDataPacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACK_CHUNK_DATA_PACKET;
	const PACKET_NAME = "RESOURCE_PACK_CHUNK_DATA_PACKET";

	public $resourcePackId = "";
	public $chunkIndex = 0;
	public $chunkData = "";

	public function decode($playerProtocol) {}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->resourcePackId);
		$this->putLInt($this->chunkIndex);
		$this->putLLong(ResourcePackDataInfoPacket::MAX_CHUNK_SIZE * $this->chunkIndex);
		$this->putLInt(strlen($this->chunkData));
		$this->put($this->chunkData);
	}

}

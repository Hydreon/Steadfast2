<?php

namespace pocketmine\network\protocol;

class ResourcePackClientResponsePacket extends PEPacket {

	const NETWORK_ID = Info::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET;
	const PACKET_NAME = "RESOURCE_PACKS_CLIENT_RESPONSE_PACKET";
	const STATUS_REFUSED = 1;
	const STATUS_SEND_PACKS = 2;
	const STATUS_HAVE_ALL_PACKS = 3;
	const STATUS_COMPLETED = 4;

	public $status;
	public $packIds = [];

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->status = $this->getByte();
		$entryCount = $this->getLShort();
		while ($entryCount-- > 0) {
			$this->packIds[] = $this->getString();
		}
	}

	public function encode($playerProtocol) {
		
	}

}

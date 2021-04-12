<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class PlayerHotbarPacket extends PEPacket {
	
	const NETWORK_ID = Info331::PLAYER_HOTBAR_PACKET;
	const PACKET_NAME = "PLAYER_HOTBAR_PACKET";
	
	public $selectedSlot;
	public $slotsLink;
	
	public function decode($playerProtocol) {
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->selectedSlot);
		$this->putByte(0); // container ID, 0 - player inventory
		$this->putByte(false); // Should select slot (don't know how it works)
	}
	
}

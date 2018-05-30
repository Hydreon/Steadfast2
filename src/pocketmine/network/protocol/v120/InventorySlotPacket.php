<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class InventorySlotPacket extends PEPacket {
	
	const NETWORK_ID = Info120::INVENTORY_SLOT_PACKET;
	const PACKET_NAME = "INVENTORY_SLOT_PACKET";
	
	/** @var integer */
	public $containerId;
	/** @var integer */
	public $slot;
	/** @var Item */
	public $item = null;
	
	public function decode($playerProtocol) {
		var_dump('decode: ' . __CLASS__);
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->containerId);
		$this->putVarInt($this->slot);
		if ($this->item == null) {
			$this->putSignedVarInt(0);
		} else {
			$this->putSlot($this->item, $playerProtocol);
		}
	}
}

<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class ShowStoreOfferPacket extends PEPacket {
	
	const NETWORK_ID = Info120::SHOW_STORE_OFFER_PACKET;
	const PACKET_NAME = "SHOW_STORE_OFFER_PACKET";
	
	/** @var string */
	public $productId = '';
	/** @var boolean */
	public $isShowToAll = false;
	
	public function decode($playerProtocol) {
		// only we send this packet, not client
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putString($this->productId);
		$this->putByte($this->isShowToAll);
	}

}

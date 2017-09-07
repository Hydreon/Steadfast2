<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class ModalFormResponsePacket extends PEPacket {

	const NETWORK_ID = Info120::MODAL_FORM_RESPONSE_PACKET;
	const PACKET_NAME = "MODAL_FORM_RESPONSE_PACKET";

	public $formId;
	public $data;

	public function encode($playerProtocol) {
		
	}

	/**
	 * Data will be null if player close form without submit
	 * (by cross button or ESC)
	 * 
	 * @param integer $playerProtocol
	 */
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->formId = $this->getVarInt();
		$this->data = $this->getString();
	}

}

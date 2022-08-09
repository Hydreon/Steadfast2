<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class ModalFormResponsePacket extends PEPacket {

	const NETWORK_ID = Info331::MODAL_FORM_RESPONSE_PACKET;
	const PACKET_NAME = "MODAL_FORM_RESPONSE_PACKET";

	public $formId;
	public $data;
	public $cancelReason;

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
		if ($playerProtocol >= Info::PROTOCOL_544) {
			$this->data = $this->getByte() === 1 ? $this->getString() : null;
			$this->cancelReason = $this->getByte() === 1 ? $this->getByte() : null;
		} else {
			$this->data = $this->getString();
			$this->cancelReason = null;
		}
	}

}

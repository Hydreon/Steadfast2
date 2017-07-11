<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class ModalFormResponsePacket extends PEPacket {

	const NETWORK_ID = Info120::MODAL_FORM_REAPONSE_PACKET;
	const PACKET_NAME = "MODAL_FORM_REAPONSE_PACKET";

	public $formId;
	public $data;

	public function encode($playerProtocol) {
		
	}

	public function decode($playerProtocol) {
		$this->formId = $this->getVarInt();
		$this->data = $this->getString();
	}

}

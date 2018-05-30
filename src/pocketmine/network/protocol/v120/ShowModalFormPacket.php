<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class ShowModalFormPacket extends PEPacket {

	const NETWORK_ID = Info120::MODAL_FORM_REQUEST_PACKET;
	const PACKET_NAME = "MODAL_FORM_REQUEST_PACKET";

	public $formId;
	public $data;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->formId);
		$this->putString($this->data);
	}

	public function decode($playerProtocol) {
		
	}

}

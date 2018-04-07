<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\PEPacket;
use pocketmine\network\protocol\Info120;

class ServerSettingsResponsetPacket extends PEPacket {
	const NETWORK_ID = Info120::SERVER_SETTINGS_RESPONSE_PACKET;

	public $formId;
	public $data;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->formId);
		$this->putString($this->data);
	}

}

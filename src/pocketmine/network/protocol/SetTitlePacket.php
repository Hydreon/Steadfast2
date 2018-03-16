<?php

namespace pocketmine\network\protocol;

class SetTitlePacket extends PEPacket {

	const NETWORK_ID = Info105::SET_TITLE_PACKET;
	const PACKET_NAME = "SET_TITLE_PACKET";
	const TITLE_TYPE_CLEAR = 0;
	const TITLE_TYPE_RESET = 1;
	const TITLE_TYPE_TITLE = 2;
	const TITLE_TYPE_SUBTITLE = 3;
	const TITLE_TYPE_ACTION_BAR = 4;
	const TITLE_TYPE_TIMES = 5;

	public $type;
	public $text;
	public $fadeInTime = 0;
	public $stayTime = 10;
	public $fadeOutTime = 0;
	public $authorXUID = "";

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->type);
		$this->putString($this->text);
		if ($playerProtocol >= Info::PROTOCOL_221) {
			$this->putString($this->authorXUID);
		}
		$this->putSignedVarInt($this->fadeInTime);
		$this->putSignedVarInt($this->stayTime);
		$this->putSignedVarInt($this->fadeOutTime);
	}

	public function decode($playerProtocol) {
		
	}

}

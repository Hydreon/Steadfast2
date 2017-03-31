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
	public $fadeInTime;
	public $stayTime;
	public $fadeOutTime;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->type);
		$this->putString($this->text);
		switch ($this->type) {
			case self::TITLE_TYPE_TIMES:
				$this->putSignedVarInt($this->fadeInTime);
				$this->putSignedVarInt($this->stayTime);
				$this->putSignedVarInt($this->fadeOutTime);
				break;
		}
	}

	public function decode($playerProtocol) {
		
	}

}

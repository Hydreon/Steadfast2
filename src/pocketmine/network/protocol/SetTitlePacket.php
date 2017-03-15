<?php

namespace pocketmine\network\protocol;

class SetTitlePacket extends DataPacket {
    
    const NETWORK_ID = Info::SET_TITLE_PACKET;
    
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
    
    public function decode() {
	}

	public function encode() {
		$this->reset();
        $this->putSignedVarInt($this->type);
        $this->putString($this->text);
        $this->putSignedVarInt($this->fadeInTime);
        $this->putSignedVarInt($this->stayTime);
        $this->putSignedVarInt($this->fadeOutTime);
	}
    
}

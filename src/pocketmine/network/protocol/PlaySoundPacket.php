<?php


namespace pocketmine\network\protocol;

class PlaySoundPacket extends PEPacket {

    const NETWORK_ID = Info120::PLAY_SOUND_PACKET;
    const PACKET_NAME = "PLAY_SOUND_PACKET";

    public $string1;

    public $x;
    public $y;
    public $z;

    public $float1;
    public $float2;

    public function decode($playerProtocol){
        $this->string1 = $this->getString();
        $this->getBlockPosition($this->x, $this->y, $this->z);
        $this->float1 = $this->getLFloat();
        $this->float2 = $this->getLFloat();
    }

    public function encode($playerProtocol){
        $this->reset($playerProtocol);
        $this->putString($this->string1);
        $this->putBlockPosition($this->x, $this->y, $this->z);
        $this->putLFloat($this->float1);
        $this->putLFloat($this->float2);
    }

}
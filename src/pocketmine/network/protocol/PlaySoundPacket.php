<?php


namespace pocketmine\network\protocol;


class PlaySoundPacket extends DataPacket {

    const NETWORK_ID = Info::PLAY_SOUND_PACKET;
    const PACKET_NAME = "PLAY_SOUND_PACKET";

    public $soundName;

    public $x;
    public $y;
    public $z;

    public $volume;
    public $pitch;

    public function decode($playerProtocol){
        $this->soundName = $this->getString();
        $this->getBlockPosition($this->x, $this->y, $this->z);
        $this->x /= 8;
        $this->y /= 8;
        $this->z /= 8;
        $this->volume = $this->getLInt();
        $this->pitch = $this->getLInt();
    }

    public function encode($playerProtocol){
        //$this->reset();
        $this->putString($this->soundName);
        $this->putBlockPosition((int) ($this->x * 8), (int) ($this->y * 8), (int) ($this->z * 8));
        $this->putLInt($this->volume);
        $this->putLInt($this->pitch);
    }

}
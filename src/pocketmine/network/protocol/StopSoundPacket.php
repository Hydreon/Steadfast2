<?php


namespace pocketmine\network\protocol;

class StopSoundPacket extends PEPacket {

    const NETWORK_ID = Info120::STOP_SOUND_PACKET;
    const PACKET_NAME = "STOP_SOUND_PACKET";

    public $string1;

    public $stopAll;

    public function decode($playerProtocol){
        $this->string1 = $this->getString();
        $this->stopAll = $this->getBool();
    }

    public function encode($playerProtocol){
        $this->reset($playerProtocol);
        $this->putString($this->string1);
        $this->putBool($this->stopAll);
    }

    public function handle(NetworkSession $session) : bool{
        return $session->handleStopSound($this);
    }

}
<?php


namespace pocketmine\network\protocol;


use pocketmine\network\mcpe\NetworkSession;
class StopSoundPacket extends PEPacket {
    const NETWORK_ID = Info::STOP_SOUND_PACKET;
    /** @var string */
    public $soundName;
    /** @var bool */
    public $stopAll;
    public function decode($playerProtocol){
        $this->soundName = $this->getString();
        $this->stopAll = $this->getBool();
    }
    public function encode($playerProtocol = Info::PROTOCOL_388){
        $this->reset($playerProtocol);
        $this->putString($this->soundName);
        $this->putBool($this->stopAll, true);
    }
    public function handle(NetworkSession $session) : bool{
        return $session->handleStopSound($this);
    }
}
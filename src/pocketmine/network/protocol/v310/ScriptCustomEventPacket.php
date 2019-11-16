<?php


namespace pocketmine\network\protocol\v310;


use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class ScriptCustomEventPacket extends PEPacket
{
    const NETWORK_ID = Info310::SCRIPT_CUSTOM_EVENT_PACKET;
    const PACKET_NAME = "SCRIPT_CUSTOM_EVENT_PACKET";

    /** @var string */
    public $eventName;
    /** @var string json data */
    public $eventData;

    public function encode($playerProtocol) {
        $this->reset($playerProtocol);
        $this->putString($this->eventName);
        $this->putString($this->eventData);
    }

    public function decode($playerProtocol) {
        $this->eventName = $this->getString();
        $this->eventData = $this->getString();
    }

}
<?php

namespace pocketmine\network\protocol;

class StrangePacket extends DataPacket{
    const NETWORK_ID = 0x1b;

    public $address;
    public $port = 19132;

    public function pid() {
        return 0x1b;
    }

    protected function putAddress($addr, $port, $version = 4){
        $this->putByte($version);
        if($version === 4){
            foreach(explode(".", $addr) as $b){
                $this->putByte((~((int) $b)) & 0xff);
            }
            $this->putShort($port);
        }else{
            //IPv6
        }
    }

    public function decode(){
    }

    public function encode(){
        $this->reset();
        $this->putAddress($this->address, $this->port);
    }
}
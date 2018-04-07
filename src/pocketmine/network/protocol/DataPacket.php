<?php

/*
 *       _____ _                 _ ______        _   ___
 *      / ____| |               | |  ____|      | | |__ \
 *     | (___ | |_ ___  __ _  __| | |__ __ _ ___| |_   ) |
 *      \___ \| __/ _ \/ _` |/ _` |  __/ _` / __| __| / /
 *      ____) | ||  __/ (_| | (_| | | | (_| \__ \ |_ / /_
 *     |_____/ \__\___|\__,_|\__,_|_|  \__,_|___/\__|____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Hydreon
 * @link http://hydreon.com/
 */

declare(strict_types=1);

namespace pocketmine\network\protocol;

#include <rules/DataPacket.h>

#ifndef COMPILE

#endif


use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;


abstract class DataPacket extends BinaryStream{

    const NETWORK_ID = 0;
    const PACKET_NAME = "";

    public $isEncoded = false;
    private $channel = 0;

    protected static $packetsIds = [];

    public function pid(){
        return $this::NETWORK_ID;
    }

    public function pname(){
        return $this::PACKET_NAME;
    }

    public function getName() : string{
        return (new \ReflectionClass($this))->getShortName();
    }

    public function isAvailableBeforeLogin() : bool{
        return false;
    }

    /**
     * @deprecated This adds extra overhead on the network, so its usage is now discouraged. It was a test for the viability of this.
     */
    public function setChannel($channel){
        $this->channel = (int) $channel;
        return $this;
    }

    public function getChannel(){
        return $this->channel;
    }

    public function clean(){
        $this->buffer = null;
        $this->isEncoded = false;
        $this->offset = 0;
        return $this;
    }

    public function __debugInfo(){
        $data = [];
        foreach($this as $k => $v){
            if($k === "buffer"){
                $data[$k] = bin2hex($v);
            }elseif(is_string($v) or (is_object($v) and method_exists($v, "__toString"))){
                $data[$k] = Utils::printable((string) $v);
            }else{
                $data[$k] = $v;
            }
        }

        return $data;
    }

    public static function initPackets() {
        $oClass = new \ReflectionClass ('pocketmine\network\protocol\Info');
        self::$packetsIds[Info::BASE_PROTOCOL] = $oClass->getConstants();
        $oClass = new \ReflectionClass ('pocketmine\network\protocol\Info105');
        self::$packetsIds[Info::PROTOCOL_105] = $oClass->getConstants();
        $oClass = new \ReflectionClass ('pocketmine\network\protocol\Info110');
        self::$packetsIds[Info::PROTOCOL_110] = $oClass->getConstants();
        $oClass = new \ReflectionClass ('pocketmine\network\protocol\Info120');
        self::$packetsIds[Info::PROTOCOL_120] = $oClass->getConstants();
        self::$packetsIds[Info::PROTOCOL_200] = $oClass->getConstants();
        self::$packetsIds[Info::PROTOCOL_220] = $oClass->getConstants();
        self::$packetsIds[Info::PROTOCOL_221] = $oClass->getConstants();
        self::$packetsIds[Info::PROTOCOL_240] = $oClass->getConstants();
    }

}
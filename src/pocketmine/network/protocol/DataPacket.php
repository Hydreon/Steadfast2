<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

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
		$this->reset();
		$this->isEncoded = false;
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
		$oClass = new \ReflectionClass ('pocketmine\network\protocol\Info331');
		self::$packetsIds[Info::PROTOCOL_419] = $oClass->getConstants();		
		self::$packetsIds[Info::PROTOCOL_422] = $oClass->getConstants();		
		self::$packetsIds[Info::PROTOCOL_428] = $oClass->getConstants();		
		self::$packetsIds[Info::PROTOCOL_431] = $oClass->getConstants();
		self::$packetsIds[Info::PROTOCOL_440] = $oClass->getConstants();
	}
	
}
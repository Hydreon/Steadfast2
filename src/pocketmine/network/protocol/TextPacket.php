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

use pocketmine\network\protocol\Info;

class TextPacket extends PEPacket{
	const NETWORK_ID = Info::TEXT_PACKET;
	const PACKET_NAME = "TEXT_PACKET";

	const TYPE_RAW = 0;
	const TYPE_CHAT = 1;
	const TYPE_TRANSLATION = 2;
	const TYPE_POPUP = 3;
	const TYPE_TIP = 4;
	const TYPE_SYSTEM = 5;
	const TYPE_WHISPER = 6;

	public $type;
	public $source;
	public $message;
	public $parameters = [];
	public $isLocolize = false;

	public function decode($playerProtocol){
		$this->type = $this->getByte();
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->isLocolize = $this->getByte();
		}
		switch($this->type){
			case self::TYPE_POPUP:
			case self::TYPE_CHAT:
				$this->source = $this->getString();
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
				$this->message = $this->getString();
				break;

//			case self::TYPE_TRANSLATION:
//				$this->message = $this->getString();
//				$count = $this->getByte();
//				for($i = 0; $i < $count; ++$i){
//					$this->parameters[] = $this->getString();
//				}
		}
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->type);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte($this->isLocolize);
		}
		switch($this->type){
			case self::TYPE_POPUP:
			case self::TYPE_CHAT:
            case self::TYPE_WHISPER:
				$this->putString($this->source);
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
            case self::TYPE_WHISPER:
				$this->putString($this->message);
				break;

//			case self::TYPE_TRANSLATION:
//				$this->putString($this->message);
//				$this->putByte(count($this->parameters));
//				foreach($this->parameters as $p){
//					$this->putString($p);
//				}
		}
	}

}

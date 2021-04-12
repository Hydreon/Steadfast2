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

use pocketmine\network\multiversion\MultiversionEnums;

class TextPacket extends PEPacket{
	const NETWORK_ID = Info::TEXT_PACKET;
	const PACKET_NAME = "TEXT_PACKET";

	const TYPE_RAW = 'TYPE_RAW';
	const TYPE_CHAT = 'TYPE_CHAT';
	const TYPE_TRANSLATION = 'TYPE_TRANSLATION';
	const TYPE_POPUP = 'TYPE_POPUP';
	const TYPE_JUKEBOX_POPUP = 'TYPE_JUKEBOX_POPUP';
	const TYPE_TIP = 'TYPE_TIP';
	const TYPE_SYSTEM = 'TYPE_SYSTEM';
	const TYPE_WHISPER = 'TYPE_WHISPER';
	const TYPE_ANNOUNCEMENT = 'TYPE_ANNOUNCEMENT';

	public $type;
	public $source;
	public $message;
	public $parameters = [];
	public $isLocolize = false;
	public $xuid = '';
	public $platformChatId = "";

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->type = $this->getByte();
		$this->isLocolize = $this->getByte();
		$this->type = MultiversionEnums::getMessageType($playerProtocol, $this->type);
		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
			case self::TYPE_ANNOUNCEMENT:
				$this->source = $this->getString();
				$this->message = $this->getString();
				break;
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
				$this->message = $this->getString();
				break;
			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
				$this->message = $this->getString();
				$paramCount = $this->getVarInt();
				for ($i = 0; $i < $paramCount; $i++) {
					$this->parameters[] = $this->getString();
				}
				break;
		}
		$this->xuid = $this->getString();
		$this->platformChatId = $this->getString(); // platform id
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$typeId = MultiversionEnums::getMessageTypeId($playerProtocol, $this->type);
		$this->putByte($typeId);
		$this->putByte($this->isLocolize);
		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
			case self::TYPE_ANNOUNCEMENT:
				$this->putString($this->source);
				$this->putString($this->message);
				break;
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
				$this->putString($this->message);
				break;
			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
			case self::TYPE_JUKEBOX_POPUP:
				$this->putString($this->message);
				$this->putVarInt(count($this->parameters));
				foreach ($this->parameters as $p) {
					$this->putString($p);
				}
				break;
		}
		$this->putString($this->xuid);
		$this->putString($this->platformChatId); // platform id
	}

}

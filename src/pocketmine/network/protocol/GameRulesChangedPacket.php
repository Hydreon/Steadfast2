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


class GameRulesChangedPacket extends PEPacket{
	const NETWORK_ID = Info::GAME_RULES_CHANGED_PACKET;
	const PACKET_NAME = "GAME_RULES_CHANGED_PACKET";

	/** @var array */
	public $gameRules = [];

	public function decode($playerProtocol){
		$this->getHeader($playerProtocol);
		$this->gameRules = $this->getGameRules();
	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putGameRules($this->gameRules);
	}
		/**
	 * Writes a gamerule array, members should be in the structure [name => [type, value]]
	 * TODO: implement this properly
	 *
	 * @param array $rules
	 */
	public function putGameRules(array $rules) {
		$this->putVarInt(count($rules));
		foreach($rules as $name => $rule){
			$this->putString($name);
			$this->putVarInt($rule[0]);
			switch($rule[0]){
				case 1:
					$this->putByte($rule[1]);
					break;
				case 2:
					$this->putVarInt($rule[1]);
					break;
				case 3:
					$this->putLFloat($rule[1]);
					break;
				default:
					throw new \InvalidArgumentException("Invalid gamerule type " . $rule[0]);
			}
			
		}
	}
}

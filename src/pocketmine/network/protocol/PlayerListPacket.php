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
use pocketmine\utils\TextFormat;


class PlayerListPacket extends PEPacket{
	const NETWORK_ID = Info::PLAYER_LIST_PACKET;
	const PACKET_NAME = "PLAYER_LIST_PACKET";

	const TYPE_ADD = 0;
	const TYPE_REMOVE = 1;

	/**
	 * Each entry is array
	 * 0 - UUID
	 * 1 - Player ID
	 * 2 - Player Name
	 * 3 - Skin ID
	 * 4 - Skin Data
	 * 5 - Cape Data
	 * 6 - Skin Geometry Name
	 * 7 - Skin Geometry Data
	 * 8 - XUID
	 */
	/** @var array[] */
	public $entries = [];
	public $type;

	public function clean(){
		$this->entries = [];
		return parent::clean();
	}

	public function decode($playerProtocol){

	}

	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		$this->putByte($this->type);
		$this->putVarInt(count($this->entries));
		switch ($this->type) {
			case self::TYPE_ADD:
				foreach ($this->entries as $d) {
					$this->putUUID($d[0]);
					$this->putVarInt($d[1]); // Player ID
					$this->putString($d[2]); // Player Name
					if ($playerProtocol >= Info::PROTOCOL_200) {
						$this->putString(""); // third party name
						$this->putSignedVarInt(0); // platform id
					}
					if ($playerProtocol >= Info::PROTOCOL_120) {
						$this->putString($d[3]); // Skin ID
						if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
							$this->putLInt(1); // num skins, always 1
						}
						$this->putString($d[4]); // Skin Data
						$capeData = isset($d[5]) ? $d[5] : '';
						if ($playerProtocol >= Info::PROTOCOL_200 && $playerProtocol < Info::PROTOCOL_220) {
							if (!empty($capeData)) {
								$this->putLInt(1); // isNotEmpty
								$this->putString($capeData); // Cape Data
							} else {
								$this->putLInt(0); // isEmpty
							}
						} else {
							$this->putString($capeData); // Cape Data
						}
						$this->putString(isset($d[6]) ? $d[6] : ''); // Skin Geometry Name
						$this->putString(isset($d[7]) ? $d[7] : ''); // Skin Geometry Data
//						$this->putString(''); //temp hack for prevent xbox and chat lags
						$this->putString(isset($d[8]) ? $d[8] : ''); // XUID
						if ($playerProtocol >= Info::PROTOCOL_200) {
							$this->putString(""); // platform chat id
						}
					} else {
						$this->putString('Standard_Custom');
						$this->putString($d[4]);
					}
				}
				break;
			case self::TYPE_REMOVE:
				foreach ($this->entries as $d) {
					$this->putUUID($d[0]);
				}
				break;
		} 
			
	}

}

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


use pocketmine\utils\UUID;

class LoginPacket extends DataPacket {

	const NETWORK_ID = Info::LOGIN_PACKET;

	public $username;
	public $protocol1;
	public $protocol2;
	public $clientId;
	public $clientUUID;
	public $serverAddress;
	public $clientSecret;
	public $slim = false;
	public $skinName;

	// for 0.15
	const MAGIC_NUMBER = 113;

	public $unknown3Bytes;   // terra incognita
	public $unknown4Bytes;   // terra incognita
	public $chainsDataLength;
	public $chains;
	public $playerDataLength;
	public $playerData;
	public $strangeData;   // terra incognita

	public function decode() {
		$this->unknown3Bytes = $this->get(2);
		$this->protocol1 = $this->getInt();
		$this->protocol2 = $this->getInt();
		$this->unknown4Bytes = $this->getInt();
		$this->chainsDataLength = $this->getLInt();
		$this->chains = json_decode($this->get($this->chainsDataLength), true);
		$this->playerDataLength = $this->getLInt();
		$this->playerData = $this->get($this->playerDataLength);

		$this->strangeData = substr($this->playerData, -1 * self::MAGIC_NUMBER);
		$this->playerData = substr($this->playerData, 0, -1 * self::MAGIC_NUMBER);

		$this->chains['data'] = array();
		foreach ($this->chains['chain'] as $key => $jwt) {
			$this->chains['data'][] = self::load($jwt);
		}

		$this->playerData = self::load($this->playerData);;
		$this->username = $this->chains['data'][1]['extraData']['displayName'];
		$this->clientId = $this->chains['data'][1]['extraData']['identity'];
		$this->clientUUID = UUID::fromBinary($this->chains['data'][1]['extraData']['XUID']);
		$this->serverAddress = $this->playerData['ServerAddress'];
//		$this->clientSecret = $this->getString();

		$this->skinName = $this->playerData['SkinId'];
		$this->skin = base64_decode($this->playerData['SkinData']);
	}

	public function encode() {
		
	}

	public static function load($jwsTokenString) {
		$parts = explode('.', $jwsTokenString);
		if (isset($parts[1])) {
			$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);					
			return $payload;
		}
		return "";
	}
		
}
	
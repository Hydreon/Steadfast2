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
use pocketmine\utils\Binary;
use pocketmine\network\protocol\Info;

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
	public $chainsDataLength;
	public $chains;
	public $playerDataLength;
	public $playerData;
	public $isValidProtocol = true;

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode() {
		$acceptedProtocols = Info::ACCEPTED_PROTOCOLS;
		$this->protocol1 = $this->getInt();
		if (!in_array($this->protocol1, $acceptedProtocols)) {
			$this->isValidProtocol = false;
			return;
		}

		$bodyLength = $this->getInt();
		$body = \zlib_decode($this->get($bodyLength));
		$this->chainsDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->chains = json_decode($this->getFromString($body, $this->chainsDataLength), true);

		$this->playerDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->playerData = $this->getFromString($body, $this->playerDataLength);

		$this->chains['data'] = array();
		$index = 0;
		foreach ($this->chains['chain'] as $key => $jwt) {
			$data = self::load($jwt);
			if (isset($data['extraData'])) {
				$dataIndex = $index;
			}
			$this->chains['data'][$index] = $data;
			$index++;
		}

		$this->playerData = self::load($this->playerData);
		$this->username = $this->chains['data'][$dataIndex]['extraData']['displayName'];
		$this->clientId = $this->chains['data'][$dataIndex]['extraData']['identity'];
		$this->clientUUID = UUID::fromString($this->chains['data'][$dataIndex]['extraData']['identity']);
		$this->identityPublicKey = $this->chains['data'][$dataIndex]['identityPublicKey'];

		$this->serverAddress = $this->playerData['ServerAddress'];
		$this->skinName = $this->playerData['SkinId'];
		$this->skin = base64_decode($this->playerData['SkinData']);
		$this->clientSecret = $this->playerData['ClientRandomId'];
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

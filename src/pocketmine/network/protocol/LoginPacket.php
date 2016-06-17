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

	// for 0.15
	const MAGIC_NUMBER = 113;

	public $unknown3Bytes;   // terra incognita
	public $unknown4Bytes;   // terra incognita
	public $chainsDataLength;
	public $chains;
	public $playerDataLength;
	public $playerData;
	public $strangeData;   // terra incognita
	
	public $additionalChar = "";
	public $isValidProtocol = true;

	
	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}
	
	public function decode() {	
		$addCharNumber = $this->getByte();
		if($addCharNumber > 0) {
			$this->additionalChar = chr($addCharNumber);
		}
		$acceptedProtocols = Info::ACCEPTED_PROTOCOLS;
		if($addCharNumber == 0xfe) {			
			$this->protocol1 = $this->getInt();			
			if (!in_array($this->protocol1, $acceptedProtocols)) {
				$this->isValidProtocol = false;
				return;
			}
			//var_dump($this->protocol1);
			$bodyLength = $this->getInt();
			//var_dump($bodyLength);
			$body =  \zlib_decode($this->get($bodyLength));
			//var_dump(strlen($body));
			$this->chainsDataLength = Binary::readLInt($this->getFromString($body, 4));
			//var_dump($this->chainsDataLength);
			$this->chains = json_decode($this->getFromString($body, $this->chainsDataLength), true);
			//var_dump($this->chains);
			
			

			$this->playerDataLength = Binary::readLInt($this->getFromString($body, 4));
			//var_dump($this->playerDataLength);
			$this->playerData = $this->getFromString($body, $this->playerDataLength);
			//var_dump(strlen($this->playerData));

			//$this->strangeData = substr($this->playerData, -1 * self::MAGIC_NUMBER);
			//$this->playerData = substr($this->playerData, 0, -1 * self::MAGIC_NUMBER);

			$this->chains['data'] = array();
			$index = 0;			
			foreach ($this->chains['chain'] as $key => $jwt) {
				$data = self::load($jwt);
				if(isset($data['extraData'])) {
					$dataIndex = $index;
				}
				$this->chains['data'][$index] = $data;
				$index++;
			}
//			var_dump($this->chains);
			//$this->strangeData = self::load($this->strangeData);
		
			$this->playerData = self::load($this->playerData);
			$this->username = $this->chains['data'][$dataIndex]['extraData']['displayName'];
			$this->clientId = $this->chains['data'][$dataIndex]['extraData']['identity'];
			if(isset($this->chains['data'][$dataIndex]['extraData']['XUID'])) {
				$this->clientUUID = UUID::fromBinary($this->chains['data'][$dataIndex]['extraData']['XUID']);
			} else {
				try{
				$this->clientUUID = UUID::fromBinary(substr($this->playerData['ClientRandomId'], 0, 16));
				} catch (\Exception $e) {
					$this->clientUUID =  UUID::fromBinary('2535437613357535');
				}
			}
			$this->identityPublicKey = $this->chains['data'][$dataIndex]['identityPublicKey'];
//			var_dump($this->identityPublicKey);
			
			$this->serverAddress = $this->playerData['ServerAddress'];
			$this->skinName = $this->playerData['SkinId'];
			$this->skin = base64_decode($this->playerData['SkinData']);
		} else {
			$this->username = $this->getString();
			$this->protocol1 = $this->getInt();
			$this->protocol2 = $this->getInt();
			if (!in_array($this->protocol1, $acceptedProtocols)) {
				$this->isValidProtocol = false;
				return;
			}

			$this->clientId = $this->getLong();
			$this->clientUUID = $this->getUUID();
			$this->serverAddress = $this->getString();
			$this->clientSecret = $this->getString();

			$this->skinName = $this->getString();
			$this->skin = $this->getString();
		}
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
	
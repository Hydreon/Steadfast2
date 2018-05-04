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
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\JWT;
use pocketmine\utils\UUID;

class LoginPacket extends PEPacket {

	const NETWORK_ID = Info::LOGIN_PACKET;
	const PACKET_NAME = "LOGIN_PACKET";
	const MOJANG_ROOT_KEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAE8ELkixyLcwlZryUQcu1TvPOmI2B7vX83ndnWRUaXm74wFfa5f/lwQNTfrLVHa2PmenpGI6JhIMUJaWZrjmMj90NoKNFSNBuKdm8rYiXsfaz3K36x/1U26HpG0ZxK/V1V";

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
	public $inventoryType = -1;
	public $osType = -1;
	public $xuid = '';
	public $languageCode = 'unknown';
	public $clientVersion = 'unknown';
	public $originalProtocol;
	public $skinGeometryName = "";
	public $skinGeometryData = "";
	public $capeData = "";
	public $isVerified = true;
	public $premiunSkin = "";

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode($playerProtocol) {
		$acceptedProtocols = Info::ACCEPTED_PROTOCOLS;
		$tmpData = Binary::readInt(substr($this->buffer, 1, 4));
		if ($tmpData == 0) {
			$this->getShort();
		}
		$this->protocol1 = $this->getInt();
		if (!in_array($this->protocol1, $acceptedProtocols)) {
			$this->isValidProtocol = false;
			return;
		}
		if ($this->protocol1 < Info::PROTOCOL_120) {
			$this->getByte();
		}
		$data = $this->getString();
		if ($this->protocol1 >= Info::PROTOCOL_110) {
			if (ord($data{0}) != 120 || (($decodedData = @zlib_decode($data)) === false)) {
				$body = $data;
			} else {
				$body = $decodedData;
			}
		} else {
			$body = \zlib_decode($data);
		}
		$this->chainsDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->chains = json_decode($this->getFromString($body, $this->chainsDataLength), true);

		$this->playerDataLength = Binary::readLInt($this->getFromString($body, 4));
		$this->playerData = $this->getFromString($body, $this->playerDataLength);

		$isNeedVerify = Server::getInstance()->isUseEncrypt();
		$dataIndex = $this->findDataIndex($isNeedVerify);
		if (is_null($dataIndex)) {
			$this->isValidProtocol = false;
			return;
		}
		$this->getPlayerData($dataIndex, $isNeedVerify);
	}

	public function encode($playerProtocol) {
		
	}

	private function findDataindex($isNeedVerify) {
		$dataIndex = null;
		$validationKey = null;
		$this->chains['data'] = array();
		$index = 0;
		if ($isNeedVerify) {
			foreach ($this->chains['chain'] as $key => $jwt) {
				$data = JWT::parseJwt($jwt);
				if ($data) {
					if (self::MOJANG_ROOT_KEY == $data['header']['x5u']) {
						$validationKey = $data['payload']['identityPublicKey'];
					} else if ($validationKey != null && $validationKey == $data['header']['x5u']) {
						$dataIndex = $index;
					} else {
						if (!isset($data['payload']['extraData'])) continue;
						$data['payload']['extraData']['XUID'] = "";
						$this->isVerified = false;
						$dataIndex = $index;
					}
					$this->chains['data'][$index] = $data['payload'];
					$index++;
				} else {
					$this->isVerified = false;
				}
			}
		} else {
			foreach ($this->chains['chain'] as $key => $jwt) {
				$data = self::load($jwt);
				if (isset($data['extraData'])) {
					$dataIndex = $index;
				}
				$this->chains['data'][$index] = $data;
				$index++;
			}
		}
		return $dataIndex;
	}

	private function getPlayerData($dataIndex, $isNeedVerify) {
		if ($isNeedVerify) {
			$this->playerData = JWT::parseJwt($this->playerData);
			if ($this->playerData) {
				if (!$this->playerData['isVerified']) {
					$this->isVerified = false;
				}
				$this->playerData = $this->playerData['payload'];
			} else {
				$this->isVerified = false;
				return;
			}
		} else {
			$this->playerData = self::load($this->playerData);
		}

		$this->username = $this->chains['data'][$dataIndex]['extraData']['displayName'];
		$this->clientId = $this->chains['data'][$dataIndex]['extraData']['identity'];
		$this->clientUUID = UUID::fromString($this->chains['data'][$dataIndex]['extraData']['identity']);
		$this->identityPublicKey = $this->chains['data'][$dataIndex]['identityPublicKey'];
		if (isset($this->chains['data'][$dataIndex]['extraData']['XUID'])) {
			$this->xuid = $this->chains['data'][$dataIndex]['extraData']['XUID'];
		}
		
		$this->serverAddress = $this->playerData['ServerAddress'];
		$this->skinName = $this->playerData['SkinId'];
		$this->skin = base64_decode($this->playerData['SkinData']);
		if (isset($this->playerData['SkinGeometryName'])) {
			$this->skinGeometryName = $this->playerData['SkinGeometryName'];
		}
		if (isset($this->playerData['SkinGeometry'])) {
			$this->skinGeometryData = base64_decode($this->playerData['SkinGeometry']);
		}
		$this->clientSecret = $this->playerData['ClientRandomId'];
		if (isset($this->playerData['DeviceOS'])) {
			$this->osType = $this->playerData['DeviceOS'];
		}
		if (isset($this->playerData['UIProfile'])) {
			$this->inventoryType = $this->playerData['UIProfile'];
		}
		if (isset($this->playerData['LanguageCode'])) {
			$this->languageCode = $this->playerData['LanguageCode'];
		}
		if (isset($this->playerData['GameVersion'])) {
			$this->clientVersion = $this->playerData['GameVersion'];
		}
		if (isset($this->playerData['CapeData'])) {
			$this->capeData = base64_decode($this->playerData['CapeData']);
		}
		if (isset($this->playerData["PremiumSkin"])) {
			$this->premiunSkin = $this->playerData["PremiumSkin"];
		}
		$this->originalProtocol = $this->protocol1;
		$this->protocol1 = self::convertProtocol($this->protocol1);
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
<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;
use pocketmine\utils\UUID;
use pocketmine\utils\Binary;

class LoginPacket extends PEPacket {

	const NETWORK_ID = Info120::LOGIN_PACKET;
	const PACKET_NAME = "LOGIN_PACKET";

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

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode($playerProtocol) {
		$this->getHeader(Info::PROTOCOL_120);
		$this->protocol1 = $this->getInt();
		if (!in_array($this->protocol1, Info::ACCEPTED_PROTOCOLS)) {
			$this->isValidProtocol = false;
			return;
		}	
		$body = $this->getString();
		
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
		if (!isset($dataIndex)) {
			$this->isValidProtocol = false;
			return;
		}
		
		$this->playerData = self::load($this->playerData);
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
		$this->originalProtocol = $this->protocol1;
		$this->protocol1 = self::convertProtocol($this->protocol1);
	}

	public function encode($playerProtocol) {
		
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


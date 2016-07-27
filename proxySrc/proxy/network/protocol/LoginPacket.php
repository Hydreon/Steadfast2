<?php

namespace proxy\network\protocol;

use proxy\utils\UUID;
use proxy\utils\Binary;
use proxy\network\protocol\Info;

class LoginPacket extends DataPacket {

	const NETWORK_ID = Info::LOGIN_PACKET;

	public $username;
	public $protocol1;
	public $protocol2;
	public $clientId;
	public $clientUUID;
	public $serverAddress;
	public $clientSecret;
	public $skin;
	public $skinName;
	public $chainsDataLength;
	public $chains;
	public $playerData;
	public $additionalChar = "";
	public $isValidProtocol = true;

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode() {
		$addCharNumber = $this->getByte();
		if ($addCharNumber > 0) {
			$this->additionalChar = chr($addCharNumber);
		}
		$acceptedProtocols = Info::ACCEPTED_PROTOCOLS;
		if ($addCharNumber == 0xfe) {
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

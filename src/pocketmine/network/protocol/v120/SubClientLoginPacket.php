<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\JWT;
use pocketmine\utils\UUID;

class SubClientLoginPacket extends PEPacket {

	const NETWORK_ID = Info120::SUB_CLIENT_LOGIN_PACKET;
	const PACKET_NAME = "SUB_CLIENT_LOGIN_PACKET";

	public $username;
	public $clientId;
	public $clientUUID;
	public $clientSecret;
	public $skinName;
	public $chainsDataLength;
	public $chains;
	public $playerDataLength;
	public $playerData;
	public $inventoryType = -1;
	public $xuid = '';
	public $skinGeometryName = "";
	public $skinGeometryData = "";
	public $capeData = "";
	public $isVerified = true;

	private function getFromString(&$body, $len) {
		$res = substr($body, 0, $len);
		$body = substr($body, $len);
		return $res;
	}

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$body = $this->getString();
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
		$this->chains['data'] = array();
		$index = 0;
		if ($isNeedVerify) {
			foreach ($this->chains['chain'] as $key => $jwt) {
				$data = JWT::parseJwt($jwt);
				if ($data) {
					if (!$data['isVerified']) {
						$this->isVerified = false;
					}
					if (isset($data['extraData'])) {
						$dataIndex = $index;
					}
					$this->chains['data'][$index] = $data;
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

		$this->skinName = $this->playerData['SkinId'];
		$this->skin = base64_decode($this->playerData['SkinData']);
		if (isset($this->playerData['SkinGeometryName'])) {
			$this->skinGeometryName = $this->playerData['SkinGeometryName'];
		}
		if (isset($this->playerData['SkinGeometry'])) {
			$this->skinGeometryData = base64_decode($this->playerData['SkinGeometry']);
		}
		$this->clientSecret = $this->playerData['ClientRandomId'];
		if (isset($this->playerData['UIProfile'])) {
			$this->inventoryType = $this->playerData['UIProfile'];
		}
		if (isset($this->playerData['CapeData'])) {
			$this->capeData = base64_decode($this->playerData['CapeData']);
		}
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

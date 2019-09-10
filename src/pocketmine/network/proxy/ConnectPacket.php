<?php

namespace pocketmine\network\proxy;

use pocketmine\network\proxy\Info;
use pocketmine\utils\UUID;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\PEPacket;

class ConnectPacket extends ProxyPacket {

	const NETWORK_ID = Info::CONNECT_PACKET;

	public $identifier;
	public $protocol;
	public $clientId;
	public $clientUUID;
	public $clientSecret;
	public $username;
	public $skinName;
	public $skin;
	public $viewRadius;
	public $ip;
	public $port;
	public $isValidProtocol = true;
    public $deviceOSType = -1;
    public $inventoryType = -1;
	public $isFirst = true;
	public $XUID = "";
	public $originalProtocol;
	public $capeData = "";
	public $premiunSkin = "";
	public $languageCode = 'unknown';
	public $serverAddress = "";
	public $clientVersion = 'unknown';
	public $platformChatId = "";
	public $identityPublicKey = "";
	public $playerId;
	public $transferData = "";
	public $skinGeometryName = "";
	public $skinGeometryData = "";
	public $additionalSkinData = [];

	public function decode() {
		$this->identifier = $this->getString();
		$this->protocol = $this->getInt();		
		$acceptedProtocols = ProtocolInfo::ACCEPTED_PROTOCOLS;
		if (!in_array($this->protocol, $acceptedProtocols)) {
			$this->isValidProtocol = false;
			return;
		}		
		$this->clientId = $this->getString();
		$this->clientUUID = UUID::fromString($this->clientId);
		$this->clientSecret = $this->getString();
		$this->username = $this->getString();
		$this->skinName = $this->getString();
		$this->skin = $this->getString();
		$this->viewRadius = $this->getInt();
		$this->ip = $this->getString();
		$this->port = $this->getInt();
		$this->isFirst = (bool) $this->getByte();
        $this->deviceOSType = $this->getInt();
        $this->inventoryType = $this->getInt();
		$this->XUID = $this->getString();
		$this->originalProtocol = $this->protocol;
		$this->protocol = PEPacket::convertProtocol($this->protocol);		
		$this->capeData = $this->getString();
		$this->premiunSkin = $this->getString();
		$this->languageCode = $this->getString();
		$this->serverAddress = $this->getString();
		$this->clientVersion = $this->getString();
		$this->platformChatId = $this->getString();
		$this->identityPublicKey = $this->getString();
		$this->playerId = $this->getInt();
		$this->transferData = $this->getString();
		$this->skinGeometryName = $this->getString();
		$this->skinGeometryData = $this->getString();
		if (!$this->feof()) {
			$this->additionalSkinData = json_decode($this->getString(), true);
			if (isset($this->additionalSkinData['AnimatedImageData'])) {
				foreach ($this->additionalSkinData['AnimatedImageData'] as &$animation) {
					$animation['Image'] = base64_decode($animation['Image']);
				}
			}
		}
		$this->checkSkinData($this->skin, $this->skinGeometryName, $this->skinGeometryData, $this->additionalSkinData);
	}

	public function encode() {
		
	}

}

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
	public $viewDistance;
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
		$this->viewDistance = $this->getInt();
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
	}

	public function encode() {
		
	}

}

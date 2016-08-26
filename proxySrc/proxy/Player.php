<?php

namespace proxy;

use proxy\network\protocol\DataPacket;
use proxy\network\protocol\DisconnectPacket;
use proxy\network\protocol\Info as ProtocolInfo;
use proxy\network\protocol\PlayStatusPacket;
use proxy\network\SourceInterface;
use proxy\utils\TextFormat;
use proxy\network\proxy\ConnectPacket;
use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use proxy\network\proxy\DisconnectPacket as ProxyDisconnectPacket;
use proxy\network\proxy\Info as ProtocolProxyInfo;

class Player {

	private $interface;
	public $loginData = [];
	public $creationTime = 0;
	private $randomClientId;
	private $ip;
	private $port;
	private $username = '';
	public $protocol;
	private $clientID = null;
	private $viewDistance = 64;
	private $tasks = [];
	private $identifier;
	public $closed = false;
	private $connected = false;
	private $loggedIn = false;
	private $uuid;
	private $clientSecret;
	private $skinName;
	private $skin;
	private $socket;
	public $proxyIdentifier;

	public function __construct(SourceInterface $interface, $clientID, $ip, $port) {
		$this->interface = $interface;
		$this->server = Server::getInstance();
		$this->ip = $ip;
		$this->port = $port;
		$this->clientID = $clientID;
		$this->socket = $this->server->getDefaultSocket();
		
		if (!($this->socket instanceof ProxySocket)) {
			throw new \Exception('[Plaer creaton] : Socket problem');
		}
	}

	public function getAddress() {
		return $this->ip;
	}

	public function getPort() {
		return $this->port;
	}
	
	public function getName() {
		return $this->username;
	}

	public function dataPacket(DataPacket $packet, $needACK = false, $direct = false) {
		$this->interface->putPacket($this, $packet, $needACK, $direct);
	}

	public function handleDataPacket(DataPacket $packet) {

		if ($packet->pid() === ProtocolInfo::BATCH_PACKET) {
			$this->server->getNetwork()->processBatch($packet, $this);
			return;
		}

		switch ($packet->pid()) {
			case ProtocolInfo::LOGIN_PACKET:
				if ($this->loggedIn === true) {
					break;
				}
				
				$this->connected = true;
				if ($packet->isValidProtocol === false) {
					$this->kick(TextFormat::RED . "Please switch to Minecraft: PE " . TextFormat::GREEN . "0.15.2" . TextFormat::RED . " to join.");
					break;
				}

				$this->username = TextFormat::clean($packet->username);
				$this->randomClientId = $packet->clientId;
				$this->uuid = $packet->clientUUID;
				$this->rawUUID = $this->uuid->toBinary();
				$this->clientSecret = $packet->clientSecret;
				$this->protocol = $packet->protocol1;
				$this->skin = $packet->skin;
				$this->skinName = $packet->skinName;

				$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . $this->ip . " logged in ");

				$valid = true;
				$len = strlen($packet->username);
				if ($len > 16 or $len < 3) {
					$valid = false;
				}
				for ($i = 0; $i < $len and $valid; ++$i) {
					$c = ord($packet->username{$i});
					if (($c >= ord("a") and $c <= ord("z")) or ( $c >= ord("A") and $c <= ord("Z")) or ( $c >= ord("0") and $c <= ord("9")) or $c === ord("_")
					) {
						continue;
					}
					$valid = false;
					break;
				}
				if (!$valid or $this->username === "rcon" || $this->username === "console") {
					$this->kick("Please choose a valid username.");
					return;
				}

				if (strlen($packet->skin) !== 64 * 32 * 4 && strlen($packet->skin) !== 64 * 64 * 4) {
					$this->kick("Invalid skin.");
					return;
				}

				$pk = new PlayStatusPacket();
				$pk->status = PlayStatusPacket::LOGIN_SUCCESS;
				$pk->encode();
				$this->dataPacket($pk);
				
				$this->sendFromProxyPacket($pk->buffer);
				$this->loggedIn = true;
				
				$this->sendConnectPacket();
				break;


			case ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET:
				$this->viewDistance = $packet->radius ** 2;
				$packet->encode();
				$this->sendProxyPacket(chr(Server::STANDART_PACKET_ID) . chr(0xfe) . $packet->buffer);
				break;
			default:
				break;
		}
	}

	public function kick($reason = "Disconnected from server.") {
		$message = $reason;
		if ($this->connected && !$this->closed) {
			if ($reason != "") {
				$pk = new DisconnectPacket;
				$pk->message = $reason;
				$this->dataPacket($pk, false, true);
			}
		}
		$this->close($reason, $message);

		return true;
	}

	public function close($message = "", $reason = "generic reason") {

		foreach ($this->tasks as $task) {
			$task->cancel();
		}
		$this->tasks = [];
		if ($this->connected && !$this->closed) {
			$this->connected = false;
			$this->interface->close($this, $reason);
			$this->spawned = false;
			$this->closed = false;
			$this->server->getLogger()->info(TextFormat::AQUA . $this->username . TextFormat::WHITE . "/" . $this->ip . " logged out due to " . str_replace(["\n", "\r"], [" ", ""], $reason));
		}

		$this->server->removePlayer($this);
		
		$pk = new ProxyDisconnectPacket();
		$pk->reason = $reason;
		$pk->encode();
		$this->sendProxyPacket(chr(Server::PROXY_PACKET_ID) . $pk->buffer);
		
	}

	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
		$this->proxyIdentifier = Server::$lastPlayerId++;
	}

	public function getIdentifier() {
		return $this->identifier;
	}

	public function checkPacket($buffer) {
		$pk = $this->getPacket($buffer);
		if ($pk === false) {
			return;
		}
		if (!is_null($pk)) {
			$pk->decode();
			$this->handleDataPacket($pk);
		} else {
			$this->sendProxyPacket(chr(Server::STANDART_PACKET_ID) . $buffer);
		}
	}

	private function getPacket($buffer) {
		if (ord($buffer{0}) == 0xfe) {
			$buffer = substr($buffer, 1);
			$pid = ord($buffer{0});
		} else {
			return false;
		}

		if (($data = $this->server->getNetwork()->getPacket($pid)) === null) {
			return null;
		}

		$data->setBuffer($buffer, 1);

		return $data;
	}

	public function sendConnectPacket() {
		$pk = new ConnectPacket();
		$pk->identifier = $this->identifier;
		$pk->protocol = $this->protocol;
		$pk->clientId = $this->randomClientId;
		$pk->clientUUID = $this->uuid;
		$pk->clientSecret = $this->clientSecret;
		$pk->username = $this->username;
		$pk->skinName = $this->skinName;
		$pk->skin = $this->skin;
		$pk->viewDistance = $this->viewDistance;
		$pk->ip = $this->ip;
		$pk->port = $this->port;
		$pk->encode();
		$this->sendProxyPacket(chr(Server::PROXY_PACKET_ID) . $pk->buffer);
	}
	
	public function sendProxyPacket($packet) {
		$this->socket->writeMessage(chr(Server::PLAYER_PACKET_ID) . pack('N', $this->proxyIdentifier) . $packet);
	}
	
	public function sendFromProxyPacket($buffer){		
		$pk = new EncapsulatedPacket();			
		$pk->buffer = chr(0xfe) . $buffer;
		$pk->reliability = 3;
		$flags = (RakLib::PRIORITY_NORMAL) | (RakLib::PRIORITY_NORMAL);
		$buffer = chr(RakLib::PACKET_ENCAPSULATED) . chr(strlen($this->identifier)) . $this->identifier . chr($flags) . $pk->toBinary(true);
		$this->interface->putReadyPacket($buffer);
	}
	
	public function handleProxyDataPacket($packet) {
		if ($packet->pid() === ProtocolProxyInfo::DISCONNECT_PACKET) {
			$this->kick($packet->reason);
		} elseif ($packet->pid() === ProtocolProxyInfo::REDIRECT_PACKET) {
			if ($this->socket->getIdentifier() == $packet->ip . $packet->port) {
				return;
			}			
			$this->server->checkRedirect($packet->ip, $packet->port, $this);			
		}
	}
	
	public function changeServer($socket) {
		$pk = new ProxyDisconnectPacket();
		$pk->reason = 'Change Server';
		$pk->encode();
		$this->sendProxyPacket(chr(Server::PROXY_PACKET_ID) . $pk->buffer);			
		$this->socket = $socket;
		$this->sendConnectPacket();
	}
	
	public function getSocket() {
		return $this->socket;
	}

}

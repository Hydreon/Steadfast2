<?php

namespace proxy\network;

use proxy\network\protocol\DataPacket;
use proxy\network\protocol\Info;
use proxy\Player;
use proxy\Server;
use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use proxy\utils\TextFormat;
use proxy\utils\Binary;
use proxy\utils\Utils;

class RakLibInterface implements ServerInstance, AdvancedSourceInterface {

	const HANDSHAKE = 9;
	const STATISTICS = 0;

	private $server;
	private $network;
	private $rakLib;
	private $players = [];
	private $identifiers;
	private $identifiersACK = [];
	private $interface;
	public $count = 0;
	public $maxcount = 31360;
	public $name = TextFormat::AQUA . "Life" . TextFormat::RED . "Boat ";
	private $token;

	public function setCount($count, $maxcount) {
		$this->count = $count;
		$this->maxcount = $maxcount;

		$this->interface->sendOption("name", "MCPE;" . addcslashes($this->name, ";") . ";" .
				(Info::CURRENT_PROTOCOL) . ";" .
				\proxy\MINECRAFT_VERSION_NETWORK . ";" .
				$this->count . ";" . $maxcount
		);
	}

	public function setFullName($data) {
		$this->interface->sendOption("name", $data);
	}

	public function __construct(Server $server) {

		$this->server = $server;
		$this->identifiers = new \SplObjectStorage();

		$this->rakLib = new RakLibServer($this->server->getLogger(), $this->server->getLoader(), $this->server->getPort(), $this->server->getIp() === "" ? "0.0.0.0" : $this->server->getIp());
		$this->interface = new ServerHandler($this->rakLib, $this);

		for ($i = 0; $i < 256; ++$i) {
			$this->channelCounts[$i] = 0;
		}
		$this->token = Utils::getRandomBytes(16, false);
		$this->setCount(count($this->server->getOnlinePlayers()), $this->server->getMaxPlayers());
	}

	public function setNetwork(Network $network) {
		$this->network = $network;
	}

	public function getUploadUsage() {
		return $this->network->getUpload();
	}

	public function getDownloadUsage() {
		return $this->network->getDownload();
	}

	public function doTick() {
		if (!$this->rakLib->isTerminated()) {
			$this->interface->sendTick();
		} else {
			$info = $this->rakLib->getTerminationInfo();
			$this->network->unregisterInterface($this);
			\ExceptionHandler::handler(E_ERROR, "RakLib Thread crashed [" . $info["scope"] . "]: " . (isset($info["message"]) ? $info["message"] : ""), $info["file"], $info["line"]);
		}
	}

	public function process() {
		$work = false;
		if ($this->interface->handlePacket()) {
			$work = true;
			while ($this->interface->handlePacket()) {
				
			}
		}

		if ($this->rakLib->isTerminated()) {
			$this->network->unregisterInterface($this);

			throw new \Exception("RakLib Thread crashed");
		}

		return $work;
	}

	public function closeSession($identifier, $reason) {
		if (isset($this->players[$identifier])) {
			$player = $this->players[$identifier];
			$this->identifiers->detach($player);
			unset($this->players[$identifier]);
			unset($this->identifiersACK[$identifier]);
			if (!$player->closed) {
				$player->close('', $reason);
			}
		}
	}

	public function close(Player $player, $reason = "unknown reason") {
		if (isset($this->identifiers[$player])) {
			unset($this->players[$this->identifiers[$player]]);
			unset($this->identifiersACK[$this->identifiers[$player]]);
			$this->interface->closeSession($this->identifiers[$player], $reason);
			$this->identifiers->detach($player);
		}
	}

	public function shutdown() {
		$this->interface->shutdown();
	}

	public function emergencyShutdown() {
		$this->interface->emergencyShutdown();
	}

	public function openSession($identifier, $address, $port, $clientID) {
		$player = new Player($this, $clientID, $address, $port);
		$this->players[$identifier] = $player;
		$this->identifiersACK[$identifier] = 0;
		$this->identifiers->attach($player, $identifier);
		$player->setIdentifier($identifier);
		$this->server->addPlayer($player);
	}

	public function handleEncapsulated($identifier, EncapsulatedPacket $packet, $flags) {
		if (isset($this->players[$identifier])) {
			$this->players[$identifier]->checkPacket($packet->buffer);
		}
	}

	public function blockAddress($address, $timeout = 300) {
		$this->interface->blockAddress($address, $timeout);
	}
	
	public static function getTokenString($token, $salt){
		return Binary::readInt(substr(hash("sha512", $salt . ":" . $token, true), 7, 4));
	}

	public function handleRaw($address, $port, $packet) {
		if (strlen($packet) > 2 && substr($packet, 0, 2) === "\xfe\xfd" && $this->server->getDefaultSocket() !== false) {
			$offset = 2;
			$packetType = ord($packet{$offset++});
			$sessionID = Binary::readInt(substr($packet, $offset, 4));
			$offset += 4;
			$payload = substr($packet, $offset);

			switch ($packetType) {
				case self::HANDSHAKE:
					$reply = chr(self::HANDSHAKE);
					$reply .= Binary::writeInt($sessionID);
					$reply .= self::getTokenString($this->token, $address) . "\x00";

					$this->server->getNetwork()->sendPacket($address, $port, $reply);
					break;
				case self::STATISTICS:
					$token = Binary::readInt(substr($payload, 0, 4));
					if ($token !== self::getTokenString($this->token, $address)) {
						break;
					}
					$reply = chr(self::STATISTICS);
					$reply .= Binary::writeInt($sessionID);


					if (strlen($payload) === 8) {
						$reply .= $this->server->getLongData();
					} else {
						$reply .= $this->server->getShortData();
					}
					$this->server->getNetwork()->sendPacket($address, $port, $reply);
					break;
			}
		}
	}

	public function sendRawPacket($address, $port, $payload) {
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function notifyACK($identifier, $identifierACK) {
		if (isset($this->players[$identifier])) {
			$this->players[$identifier]->handleACK($identifierACK);
		}
	}

	public function setName($name) {
		if (strlen($name) > 1) {
			$this->name = $name;
		}
	}

	public function setPortCheck($name) {
		$this->interface->sendOption("portChecking", (bool) $name);
	}

	public function handleOption($name, $value) {
		if ($name === "bandwidth") {
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = false) {
		if (isset($this->identifiers[$player])) {

			$identifier = $this->identifiers[$player];
			$pk = null;
			if (!$packet->isEncoded) {
				$packet->encode();
			} elseif (!$needACK) {
				if (isset($packet->__encapsulatedPacket)) {
					unset($packet->__encapsulatedPacket);
				}
				$packet->__encapsulatedPacket = new CachedEncapsulatedPacket;
				$packet->__encapsulatedPacket->identifierACK = null;
				$packet->__encapsulatedPacket->buffer = chr(0xfe) . $packet->buffer;
				$packet->__encapsulatedPacket->reliability = 2;
				$pk = $packet->__encapsulatedPacket;
			}


			if ($pk === null) {
				$pk = new EncapsulatedPacket();
				$pk->buffer = chr(0xfe) . $packet->buffer;
				$pk->reliability = 2;

				if ($needACK === true) {
					$pk->identifierACK = $this->identifiersACK[$identifier] ++;
				}
			}

			$this->interface->sendEncapsulated($identifier, $pk, ($needACK === true ? RakLib::FLAG_NEED_ACK : 0) | ($immediate === true ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
		}

		return null;
	}

	public function putReadyPacket($buffer) {
		$this->interface->sendReadyEncapsulated($buffer);
	}

}

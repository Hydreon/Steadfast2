<?php

namespace pocketmine\network;

use pocketmine\Server;
use pocketmine\network\proxylib\ProxyServer;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\Player;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\proxy\ProxyPacket;
use raklib\Binary;
use raklib\RakLib;
use pocketmine\network\AdvancedSourceInterface;
use pocketmine\network\Network;
use pocketmine\utils\TextFormat;
use pocketmine\network\protocol\Info;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\network\proxy\Info as ProtocolProxyInfo;

//class ProxyInterface implements SourceInterface {
class ProxyInterface implements AdvancedSourceInterface {

	const STANDART_PACKET_ID = 0x01;
	const PROXY_PACKET_ID = 0x02;
	const PLAYER_PACKET_ID = 0x03;
	const SYSTEM_PACKET_ID = 0x04;
	const SYSTEM_DATA_PACKET_ID = 0x05;

	private $identifiers;
	private $server;
	/** @var ProxyServer */
	private $proxyServer;
	private $session = array();
	private $network;
	// for raw packets. key - response recipient identifier , value - session iddentifier
	private $nonPlayerSessionMap = array();
	
	public $count = 0;
	public $maxcount = 200;
	public $name = TextFormat::AQUA . "Life" . TextFormat::RED . "Boat ";

	public function __construct(Server $server) {
		$this->server = $server;
		$this->identifiers = new \SplObjectStorage();
		$this->proxyServer = new ProxyServer($this->server->getLogger(), $this->server->getLoader(), $this->server->getProxyPort(), $this->server->getIp() === "" ? "127.0.0.1" : $this->server->getIp());
	}

	public function emergencyShutdown() {
		
	}

	public function setName($name) {
		if (strlen($name) > 1) {
			$this->name = $name;
		}
	}

	public function shutdown() {
		$this->proxyServer->shutdown();
	}

	public function setCount($count, $maxcount) {
		$this->count = $count;
		$this->maxcount = $maxcount;
	}

	public function process() {
		$packets = [];
		while ($info = $this->proxyServer->readFromProxyServer()) {
			$packets[] = $info;
		}
		foreach ($packets as $packet) {
			$idlen = ord($packet{0});
			$dataIdentifier = substr($packet, 1, $idlen);
			$data = substr($packet, 1 + $idlen);
			$this->checkPacketData($dataIdentifier, $data);
		}
	}

	private function checkPacketData($dataIdentifier, $data) {		
		if ($data == 'close') {
			foreach ($this->session as $sessionId => $session) {
				if (strpos($sessionId, $dataIdentifier) === 0) {
					$session->close('', 'Proxy disconnect');
				}
			}
			return;
		}
		$offset = 0;
		$packetType = ord($data{0});
		if ($packetType == static::PLAYER_PACKET_ID) {
			$sessionId = unpack('N', substr($data, 1, 4));
			$sessionId = $sessionId[1];
			$buffer = substr($data, 5);
			$identifier = $dataIdentifier . $sessionId;
			if (!isset($this->session[$identifier])) {
				if(ord($buffer{0}) == self::PROXY_PACKET_ID && ord($buffer{1}) == ProtocolProxyInfo::CONNECT_PACKET) {
					$this->openSession($dataIdentifier, $sessionId);
				}
			}
			if (isset($this->session[$identifier])) {
				$player = $this->session[$identifier];
				$type = ord($buffer{0});
				$buffer = substr($buffer, 1);
				if ($type == self::STANDART_PACKET_ID) {
					$pk = $this->getPacket($buffer, $player->getPlayerProtocol());
					if ($pk === false) {
						return;
					}
					if (!is_null($pk)) {
						try {
							$pk->decode($player->getPlayerProtocol());
							$player->handleDataPacket($pk);
						} catch (\Exception $e) {
							echo "DECODE ERROR: " . $e->getMessage() . ", PACKET ID: " . $pk->pid();
						}
					}
				} elseif ($type == self::PROXY_PACKET_ID) {
					$pk = $this->getProxyPacket($buffer);
					if ($pk === false) {
						return;
					}
					if (!is_null($pk)) {
						try {
							$pk->decode($player->getPlayerProtocol());
							$player->handleProxyDataPacket($pk);
						} catch (\Exception $e) {
							echo "DECODE ERROR: " . $e->getMessage() . ", PROXY PACKET ID: " . $pk->pid();
						}
					}
				}
			}
		} else if ($packetType == static::SYSTEM_PACKET_ID) {
			$packet = substr($data, 1);
			$id = ord($packet{0});
			$offset = 1;
			if ($id === RakLib::PACKET_RAW) {
				$len = ord($packet{$offset++});
				$address = substr($packet, $offset, $len);
				$offset += $len;
				$port = Binary::readShort(substr($packet, $offset, 2));
				$offset += 2;
				$payload = substr($packet, $offset);

				$endPointId = $address . ':' . $port;
				if (isset($this->nonPlayerSessionMap[$endPointId]) &&
						$this->nonPlayerSessionMap[$endPointId] != $dataIdentifier) {

					return true;
				} else if (!isset($this->nonPlayerSessionMap[$endPointId])) {
					$this->nonPlayerSessionMap[$endPointId] = $dataIdentifier;
				}

				Server::getInstance()->handlePacket($address, $port, $payload);
			}
		} else if ($packetType == self::SYSTEM_DATA_PACKET_ID) {
			$packet = substr($data, 1);
			$id = ord($packet{0});
			if ($id = 0x01) {
				$this->server->getPluginManager()->callEvent($ev = new QueryRegenerateEvent($this->server, 5));
				$outputData = array();
				$outputData['longData'] = $ev->getLongQuery();
				$outputData['shortData'] = $ev->getShortQuery();
				$outputData['name'] = "MCPE;" . addcslashes($this->name, ";") . ";" .
						(Info::CURRENT_PROTOCOL) . ";" .
						\pocketmine\MINECRAFT_VERSION_NETWORK . ";" .
						$this->count . ";" . $this->maxcount;

				
				$info = chr(strlen($dataIdentifier)) . $dataIdentifier . 'close';
				$infoData =  chr(self::SYSTEM_DATA_PACKET_ID) . chr(0x02) .
					pack('N', strlen($outputData['name'])) . $outputData['name'] .
					pack('N', strlen($outputData['longData'])) . $outputData['longData'] .
					pack('N', strlen($outputData['shortData'])) . $outputData['shortData'];
				$info = chr(strlen($dataIdentifier)) . $dataIdentifier . $infoData;
				$this->proxyServer->writeToProxyServer($info);
			}
		}
	}

	public function openSession($id, $sessionId) {
		$data = explode(':', $id);
		$address = $data[0];
		$port = $data[1];
		$identifier = $id . $sessionId;
		$ev = new PlayerCreationEvent($this, Player::class, Player::class, null, $address, $port);
		$this->server->getPluginManager()->callEvent($ev);
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getClientId(), $ev->getAddress(), $ev->getPort());
		$this->session[$identifier] = $player;
		$player->setIdentifier($identifier, $id, $sessionId);
		$this->server->addPlayer($identifier, $player);
	}

	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = false) {
		if (isset($this->session[$player->getIdentifier()])) {
			if (!$packet->isEncoded) {
				$packet->encode($player->getPlayerProtocol());
			}
			if ($packet instanceof ProxyPacket) {
				$type = chr(self::PROXY_PACKET_ID);
			} else {
				$type = chr(self::STANDART_PACKET_ID);
			}

			$infoData =  chr(static::PLAYER_PACKET_ID) . pack('N', $player->proxySessionId) . $type . $packet->buffer;			
			$info = chr(strlen($player->proxyId)) . $player->proxyId . $infoData;
			
			$this->proxyServer->writeToProxyServer($info);
		}
	}

	public function close(Player $player, $reason = "unknown reason") {
		unset($this->session[$player->getIdentifier()]);
	}

	public function putReadyPacket($player, $buffer) {
		if (isset($this->session[$player->getIdentifier()])) {	
			$infoData = chr(static::PLAYER_PACKET_ID) . pack('N', $player->proxySessionId) . chr(self::STANDART_PACKET_ID) . $buffer;
			$info = chr(strlen($player->proxyId)) . $player->proxyId . $infoData;
			$this->proxyServer->writeToProxyServer($info);
		}
	}

	private function getPacket($buffer, $playerProtocol) {
		if (ord($buffer{0}) == 0xfe) {
			$buffer = substr($buffer, 1);
			if (empty($buffer)) {
				return false;
			}
			$pid = ord($buffer{0});
		} else {
			return false;
		}

		if (($data = $this->server->getNetwork()->getPacket($pid, $playerProtocol)) === null) {
			return null;
		}

		$data->setBuffer($buffer, 1);

		return $data;
	}

	private function getProxyPacket($buffer) {
		if (empty($buffer)) {
			return false;
		}
		$pid = ord($buffer{0});

		if (($data = $this->server->getNetwork()->getProxyPacket($pid)) === null) {
			return null;
		}

		$data->setBuffer($buffer, 1);

		return $data;
	}

	public function blockAddress($address, $timeout = 300) {}
	
	public function setNetwork(Network $network) {
		$this->network = $network;
	}
	
	public function sendRawPacket($address, $port, $payload) {}
}

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
		
	}

	public function setCount($count, $maxcount) {
		$this->count = $count;
		$this->maxcount = $maxcount;
	}

	public function process() {
		while ($info = $this->proxyServer->readFromProxyServer()) {
			$data = unserialize($info);
			if ($data['data'] == 'close') {
				foreach ($this->session as $sessionId => $session) {
					if(strpos($sessionId,  $data['id']) === 0) {
						$session->close('', 'Proxy disconnect');
					}
				}
				return;
			}
			$offset = 0;
			$packetType = ord($data['data']{0});
			if ($packetType == static::PLAYER_PACKET_ID) {
				$sessionId = unpack('N', substr($data['data'], 1, 4));
				$sessionId = $sessionId[1];
				$buffer = substr($data['data'], 5);
				$id = $data['id'];
				$identifier = $id . $sessionId;
				if (!isset($this->session[$identifier])) {
					$this->openSession($id, $sessionId);
				}
				if (isset($this->session[$identifier])) {
					$type = ord($buffer{0});
					$buffer = substr($buffer, 1);
					if ($type == self::STANDART_PACKET_ID) {
						$pk = $this->getPacket($buffer);
						if ($pk === false) {
							return;
						}
						if (!is_null($pk)) {
							$pk->decode();
							$this->session[$identifier]->handleDataPacket($pk);
						}
					} elseif ($type == self::PROXY_PACKET_ID) {
						$pk = $this->getProxyPacket($buffer);
						if ($pk === false) {
							return;
						}
						if (!is_null($pk)) {
							$pk->decode();
							$this->session[$identifier]->handleProxyDataPacket($pk);
						}
					}
				}
			} else if ($packetType == static::SYSTEM_PACKET_ID) {
				$packet = substr($data['data'], 1);
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
						$this->nonPlayerSessionMap[$endPointId] != $data['id']) {
						
						return true;
					} else if (!isset($this->nonPlayerSessionMap[$endPointId])) {
						$this->nonPlayerSessionMap[$endPointId] = $data['id'];
					}
					
					Server::getInstance()->handlePacket($address, $port, $payload);
				}
			} else if ($packetType == self::SYSTEM_DATA_PACKET_ID) {
				$packet = substr($data['data'], 1);
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
					
					$info = array(
						'id' => $data['id'],
						'data' => chr(self::SYSTEM_DATA_PACKET_ID) . chr(0x02) . serialize($outputData)
					);
					$this->proxyServer->writeToProxyServer(serialize($info));				
				}
			}
		}
		return true;
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
				$packet->encode();
			}
			if ($packet instanceof ProxyPacket) {
				$type = chr(self::PROXY_PACKET_ID);
			} else {
				$type = chr(self::STANDART_PACKET_ID);
			}
			$info = array(
				'id' => $player->proxyId,
				'data' => chr(static::PLAYER_PACKET_ID) . pack('N', $player->proxySessionId) . $type . $packet->buffer
			);
			$this->proxyServer->writeToProxyServer(serialize($info));
		}
	}

	public function close(Player $player, $reason = "unknown reason") {
		unset($this->session[$player->getIdentifier()]);
	}

	public function putReadyPacket($player, $buffer) {
		if (isset($this->session[$player->getIdentifier()])) {	
			$info = array(
				'id' => $player->proxyId,
				'data' => chr(static::PLAYER_PACKET_ID) . pack('N', $player->proxySessionId) . chr(self::STANDART_PACKET_ID) . $buffer
			);
			$this->proxyServer->writeToProxyServer(serialize($info));	
		}
	}

	private function getPacket($buffer) {
		if (ord($buffer{0}) == 0xfe) {
			$buffer = substr($buffer, 1);
			if (empty($buffer)) {
				return false;
			}
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
	
	public function sendRawPacket($address, $port, $payload) {
		$endPointId = $address . ':' . $port;
		if (!isset($this->nonPlayerSessionMap[$endPointId])) {
			return;
		}
		
		$payload = chr(static::SYSTEM_PACKET_ID) . chr(RakLib::PACKET_RAW) . chr(strlen($address)) . $address . Binary::writeShort($port) . $payload;
		$data = array(
			'id' => $this->nonPlayerSessionMap[$endPointId],
			'type' => 'raw',
			'data' => $payload
		);
		$this->proxyServer->writeToProxyServer(serialize($data));
		unset($this->nonPlayerSessionMap[$endPointId]);
	}
}

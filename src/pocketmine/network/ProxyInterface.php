<?php

namespace pocketmine\network;

use pocketmine\Server;
use pocketmine\network\proxylib\ProxyServer;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\Player;
use pocketmine\network\AdvancedSourceInterface;
use pocketmine\network\Network;
use pocketmine\utils\TextFormat;
use pocketmine\network\proxy\Info as ProtocolProxyInfo;
use pocketmine\network\proxylib\RemoteProxyServer;

//class ProxyInterface implements SourceInterface {
class ProxyInterface implements AdvancedSourceInterface {

	const STANDART_PACKET_ID = 0x01;
	const PROXY_PACKET_ID = 0x02;

	private $identifiers;
	private $server;
	/** @var ProxyServer */
	private $proxyServer;
	private $session = array();
	private $network;
	
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
		$sessionId = unpack('N', substr($data, 0, 4));
		$sessionId = $sessionId[1];
		$buffer = substr($data, 4);
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
				$pk = $this->getPacket($buffer, $player);
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
	
	public function putPacket(Player $player, $buffer, $isProxyPacket = false) {
		if (isset($this->session[$player->getIdentifier()])) {
			//TODO: add FLAG_NEED_ZLIB_RAW here and check player protocol
			if ($isProxyPacket) {
				$infoData = pack('N', $player->proxySessionId) . chr(self::PROXY_PACKET_ID | RemoteProxyServer::FLAG_NEED_ZLIB) . $buffer;	
			} else {
				$infoData = pack('N', $player->proxySessionId) . chr(self::STANDART_PACKET_ID | RemoteProxyServer::FLAG_NEED_ZLIB) . $buffer;	
			}		
			$info = chr(strlen($player->proxyId)) . $player->proxyId . $infoData;
			
			$this->proxyServer->writeToProxyServer($info);
		}
	}

	public function close(Player $player, $reason = "unknown reason") {
		unset($this->session[$player->getIdentifier()]);
	}

	public function putReadyPacket($player, $buffer) {
		if (isset($this->session[$player->getIdentifier()])) {	
			$infoData = pack('N', $player->proxySessionId) . chr(self::STANDART_PACKET_ID) . $buffer;
			$info = chr(strlen($player->proxyId)) . $player->proxyId . $infoData;
			$this->proxyServer->writeToProxyServer($info);
		}
	}

	private function getPacket($buffer, $player) {
		$pid = ord($buffer{0});
		if ($pid == 0x13) { //speed hack
			$player->setLastMovePacket($buffer);
			return null;
		}

		if (($data = $this->server->getNetwork()->getPacket($pid, $player->getPlayerProtocol())) === null) {
			return null;
		}

		$data->setBuffer($buffer);

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

	public function getRaklib(){
		return $this->proxyServer;
	}
}

<?php

namespace pocketmine\network;

use pocketmine\Server;
use pocketmine\network\proxylib\ProxyServer;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\Player;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\proxy\ProxyPacket;

class ProxyInterface implements SourceInterface {

	const STANDART_PACKET_ID = 0x01;
	const PROXY_PACKET_ID = 0x02;

	private $identifiers;
	private $server;
	private $proxyServer;
	private $session = array();

	public function __construct(Server $server) {
		$this->server = $server;
		$this->identifiers = new \SplObjectStorage();
		$this->proxyServer = new ProxyServer($this->server->getLogger(), $this->server->getLoader(), $this->server->getProxyPort(), $this->server->getIp() === "" ? "127.0.0.1" : $this->server->getIp());
	}

	public function emergencyShutdown() {
		
	}

	public function setName($name) {
		
	}

	public function shutdown() {
		
	}

	public function setCount($count, $maxcount) {
		
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
			$sessionId = unpack('N', substr($data['data'], 0, 4));
			$sessionId = $sessionId[1];
			$buffer = substr($data['data'], 4);
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
			$packet->updateBuffer($player->getAdditionalChar());
			$info = array(
				'id' => $player->proxyId,
				'data' => pack('N', $player->proxySessionId) . $type . $packet->buffer
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
				'data' => pack('N', $player->proxySessionId) . chr(self::STANDART_PACKET_ID) . $buffer
			);
			$this->proxyServer->writeToProxyServer(serialize($info));	
		}
	}

	private function getPacket($buffer) {
		if (ord($buffer{0}) == 254) {
			$buffer = substr($buffer, 1);
			$additionalChar = chr(0xfe);
			$pid = DataPacket::$pkKeys[ord($buffer{0})];
		} elseif (ord($buffer{0}) == 142) {
			$buffer = substr($buffer, 1);
			$additionalChar = chr(0x8e);
			$pid = ord($buffer{0});
		} else {
			return false;
		}

		if (($data = $this->server->getNetwork()->getPacket($pid)) === null) {
			return null;
		}
		if ($pid == 0x8f) {
			$buffer = chr($pid) . $additionalChar . substr($buffer, 1);
		}

		$data->setBuffer($buffer, 1);

		return $data;
	}

	private function getProxyPacket($buffer) {
		$pid = ord($buffer{0});

		if (($data = $this->server->getNetwork()->getProxyPacket($pid)) === null) {
			return null;
		}

		$data->setBuffer($buffer, 1);

		return $data;
	}

}

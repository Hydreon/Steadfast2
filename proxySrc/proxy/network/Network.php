<?php

namespace proxy\network;

use proxy\network\protocol\BatchPacket;
use proxy\network\protocol\DataPacket;
use proxy\network\protocol\Info;
use proxy\network\protocol\Info as ProtocolInfo;
use proxy\network\protocol\DisconnectPacket;
use proxy\network\protocol\LoginPacket;
use proxy\network\protocol\PlayStatusPacket;
use proxy\Player;
use proxy\Server;
use proxy\utils\MainLogger;
use proxy\network\protocol\RequestChunkRadiusPacket;
use proxy\utils\Binary;
use proxy\network\proxy\DisconnectPacket as ProxyDisconnectPacket;
use proxy\network\proxy\Info as ProtocolProxyInfo;
use proxy\network\proxy\RedirectPacket;

class Network {

	public static $BATCH_THRESHOLD = 512;
	private $packetPool;
	private $proxyPacketPool;
	private $server;
	private $interfaces = [];
	private $advancedInterfaces = [];
	private $upload = 0;
	private $download = 0;
	private $name;

	public function __construct(Server $server) {

		$this->registerPackets();
		$this->registerProxyPackets();

		$this->server = $server;
	}

	public function addStatistics($upload, $download) {
		$this->upload += $upload;
		$this->download += $download;
	}

	public function getUpload() {
		return $this->upload;
	}

	public function getDownload() {
		return $this->download;
	}

	public function resetStatistics() {
		$this->upload = 0;
		$this->download = 0;
	}

	public function getInterfaces() {
		return $this->interfaces;
	}

	public function setCount($count, $maxcount = 31360) {
		$this->server->mainInterface->setCount($count, $maxcount);
	}

	public function processInterfaces() {
		foreach ($this->interfaces as $interface) {
			try {
				$interface->process();
			} catch (\Exception $e) {
				$logger = $this->server->getLogger();
				$interface->emergencyShutdown();
				$this->unregisterInterface($interface);
				$logger->critical("Network error: " . $e->getMessage());
			}
		}
	}

	public function registerInterface(SourceInterface $interface) {
		$this->interfaces[$hash = spl_object_hash($interface)] = $interface;
		if ($interface instanceof AdvancedSourceInterface) {
			$this->advancedInterfaces[$hash] = $interface;
			$interface->setNetwork($this);
		}
		$interface->setName($this->name);
	}

	public function unregisterInterface(SourceInterface $interface) {
		unset($this->interfaces[$hash = spl_object_hash($interface)], $this->advancedInterfaces[$hash]);
	}

	public function setName($name) {
		$this->name = (string) $name;
		foreach ($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}

	public function getName() {
		return $this->name;
	}

	public function updateName() {
		foreach ($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}

	public function registerPacket($id, $class) {
		$this->packetPool[$id] = new $class;
	}

	public function registerProxyPacket($id, $class) {
		$this->proxyPacketPool[$id] = new $class;
	}

	public function getServer() {
		return $this->server;
	}

	public function processBatch(BatchPacket $packet, Player $p) {
		$str = \zlib_decode($packet->payload, 1024 * 1024 * 64);
		$len = strlen($str);
		$offset = 0;
		try {
			while ($offset < $len) {
				$pkLen = Binary::readInt(substr($str, $offset, 4));
				$offset += 4;
				$buf = substr($str, $offset, $pkLen);
				if(empty($buf)) {
					return;
				}
				$offset += $pkLen;
				$pid = ord($buf{0});
				$originBuf = $buf;
				$buf = substr($buf, 1);
				if (($pk = $this->getPacket($pid)) !== null) {
					if ($pk::NETWORK_ID === Info::BATCH_PACKET) {
						throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
					}
					$pk->setBuffer($buf);
					$pk->decode();
					$p->handleDataPacket($pk);
					if ($pk->getOffset() <= 0) {
						return;
					}
				} else {
					$p->sendProxyPacket(chr(0x01) . chr(0xfe) . $originBuf);
				}
			}
		} catch (\Exception $e) {
			$logger = $this->server->getLogger();
			if ($logger instanceof MainLogger) {
				$logger->logException($e);
			}
		}
	}

	public function getPacket($id) {
		$class = $this->packetPool[$id];
		if ($class !== null) {
			return clone $class;
		}
		return null;
	}

	public function getProxyPacket($id) {
		$class = $this->proxyPacketPool[$id];
		if ($class !== null) {
			return clone $class;
		}
		return null;
	}

	public function sendPacket($address, $port, $payload) {
		foreach ($this->advancedInterfaces as $interface) {
			$interface->sendRawPacket($address, $port, $payload);
		}
	}

	public function blockAddress($address, $timeout = 300) {
		foreach ($this->advancedInterfaces as $interface) {
			$interface->blockAddress($address, $timeout);
		}
	}

	private function registerPackets() {
		$this->packetPool = new \SplFixedArray(256);

		$this->registerPacket(ProtocolInfo::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket(ProtocolInfo::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket(ProtocolInfo::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket(ProtocolInfo::BATCH_PACKET, BatchPacket::class);
		$this->registerPacket(ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
	}

	private function registerProxyPackets() {
		$this->proxyPacketPool = new \SplFixedArray(256);
		$this->registerProxyPacket(ProtocolProxyInfo::DISCONNECT_PACKET, ProxyDisconnectPacket::class);
		$this->registerProxyPacket(ProtocolProxyInfo::REDIRECT_PACKET, RedirectPacket::class);
	}

}

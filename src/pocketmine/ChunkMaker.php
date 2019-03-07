<?php

namespace pocketmine;

use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\ProxyInterface;

class ChunkMaker extends Thread {

	protected $classLoader;
	protected $shutdown;
	protected $internalQueue;
	protected $raklib;
	protected $proxy;

	public function __construct(\ClassLoader $loader, $raklib, $proxy) {
		$this->internalQueue = new \Threaded;
		$this->shutdown = false;
		$this->classLoader = $loader;
		$this->raklib = $raklib;
		$this->proxy = $proxy;
		$this->start(PTHREADS_INHERIT_CONSTANTS);
	}

	public function join() {
		$this->shutdown = true;
		parent::join();
	}

	public function run() {
		$this->registerClassLoader();
		gc_enable();
		ini_set("memory_limit", -1);
		ini_set("display_errors", 1);
		ini_set("display_startup_errors", 1);

		set_error_handler([$this, "errorHandler"], E_ALL);
		DataPacket::initPackets();
		new ChunkStorage($this);
	}

	public function sendData($data, $buffer) {
		if (!is_null($this->proxy) && !empty($data['proxySessionId']) && !empty($data['proxyId'])) {
			$infoData = pack('N', $data['proxySessionId']) . chr(ProxyInterface::STANDART_PACKET_ID) . $buffer;
			$info = chr(strlen($data['proxyId'])) . $data['proxyId'] . $infoData;
			$this->proxy->writeToProxyServer($info);
		} elseif(!is_null($this->raklib)) {
			$identifier = $data['identifier'];
			$pk = new EncapsulatedPacket();
			$pk->buffer = $buffer;
			$pk->reliability = 3;
			$enBuffer = chr(RakLib::PACKET_ENCAPSULATED) . chr(strlen($identifier)) . $identifier . chr(RakLib::PRIORITY_NORMAL) . $pk->toBinary(true);
			$this->raklib->pushMainToThreadPacket($enBuffer);
		}
	}

	public function pushMainToThreadPacket($data) {
		$this->internalQueue[] = $data;
	}

	public function readMainToThreadPacket() {
		return $this->internalQueue->shift();
	}

	public function isShutdown() {
		return $this->shutdown;
	}

}

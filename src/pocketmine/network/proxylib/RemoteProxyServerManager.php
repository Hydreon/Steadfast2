<?php

namespace pocketmine\network\proxylib;

class RemoteProxyServerManager {

	private $proxyServer;
	private $remoteProxyServer = [];

	public function __construct($proxyServer) {
		$this->proxyServer = $proxyServer;
	}

	public function tickProcessor() {
		while (!$this->proxyServer->isShutdown()) {
			$start = microtime(true);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.025) {
				@time_sleep_until(microtime(true) + 0.025 - $time);
			}
		}
		foreach ($this->remoteProxyServer as $remoteServer) {			
			$remoteServer->close();
		}
		$this->remoteProxyServer = [];
	}

	private function tick() {
		while (($socket = $this->proxyServer->getNewServer())) {
			$remoteProxy = new RemoteProxyServer($this, $socket);
			$this->remoteProxyServer[$remoteProxy->getIdentifier()] = $remoteProxy;
		}

		foreach ($this->remoteProxyServer as $remoteServer) {
			$remoteId = $remoteServer->getIdentifier();
			if (!$remoteServer->update()) {
				$isClosed = $remoteServer->close();
				if ($isClosed) {
					unset($this->remoteProxyServer[$remoteId]);
				} else {
					var_dump($remoteServer->getIdentifier().' is not close yet');
				}
			}
		}
		$this->getNewPacket();	
	}

	public function getLogger() {
		return $this->proxyServer->getLogger();
	}

	public function getProxyServer() {
		return $this->proxyServer;
	}

	public function getNewPacket() {
		while (($info = $this->proxyServer->readFromInternaQueue())) {		
			$idlen = ord($info{0});
			$dataIdentifier = substr($info, 1, $idlen);
			$data = substr($info, 1 + $idlen);
			if (isset($this->remoteProxyServer[$dataIdentifier])) {
				$this->remoteProxyServer[$dataIdentifier]->putPacket($data);
			}
		}
	}
	
}

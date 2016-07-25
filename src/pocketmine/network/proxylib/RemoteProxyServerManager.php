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
			if ($time < 0.01) {
				time_sleep_until(microtime(true) + 0.01 - $time);
			}
		}
	}

	private function tick() {
		while (($socket = $this->proxyServer->getNewServer())) {
			$remoteProxy = new RemoteProxyServer($this, $socket);
			$this->remoteProxyServer[$remoteProxy->getIdentifier()] = $remoteProxy;
		}

		foreach ($this->remoteProxyServer as $remoteServer) {
			if (!$remoteServer->update()) {
				$remoteServer->close();
				unset($this->remoteProxyServer[$remoteServer->getIdentifier()]);
			}
		}
		$this->getNewPacket();	}

	public function getLogger() {
		return $this->proxyServer->getLogger();
	}

	public function getProxyServer() {
		return $this->proxyServer;
	}

	public function getNewPacket() {
		while (($info = $this->proxyServer->readFromInternaQueue())) {
			$data = unserialize($info);
			if (isset($this->remoteProxyServer[$data['id']])) {
				$this->remoteProxyServer[$data['id']]->putPacket($data['data']);
			}
		}
	}

}

<?php

namespace proxy;

use proxy\network\Network;
use proxy\network\RakLibInterface;
use proxy\network\SourceInterface;
use proxy\utils\TextFormat;

class Server {

	const STANDART_PACKET_ID = 0x01;
	const PROXY_PACKET_ID = 0x02;

	public static $lastPlayerId = 1;
	private static $instance = null;
	private $isRunning = true;
	private $tickCounter;
	private $nextTick = 0;
	private $tickAverage = [20, 20, 20, 20, 20];
	private $useAverage = [20, 20, 20, 20, 20];
	private $logger;
	private $network;
	private $autoloader;
	private $players = [];
	private $sockets = [];

	public function getLoader() {
		return $this->autoloader;
	}

	public function getLogger() {
		return $this->logger;
	}

	public function getTick() {
		return $this->tickCounter;
	}

	public function getTicksPerSecond() {
		return round(array_sum($this->tickAverage) / count($this->tickAverage), 2);
	}

	public function getTickUsage() {
		return round((array_sum($this->useAverage) / count($this->useAverage)) * 100, 2);
	}

	public function getInterfaces() {
		return $this->network->getInterfaces();
	}

	public static function getInstance() {
		return self::$instance;
	}

	public function getNetwork() {
		return $this->network;
	}


	public function getMaxPlayers() {
		return 200;
	}
	
	public function getOnlinePlayers() {
		return $this->players;
	}

	public function addInterface(SourceInterface $interface) {
		$this->network->registerInterface($interface);
	}

	public function removeInterface(SourceInterface $interface) {
		$interface->shutdown();
		$this->network->unregisterInterface($interface);
	}

	public function addPlayer(Player $player) {
		$this->players[$player->proxyIdentifier] = $player;
	}

	public function removePlayer(Player $player) {
		unset($this->players[$player->proxyIdentifier]);
	}

	public function __construct(\ClassLoader $autoloader, \ThreadedLogger $logger) {
		self::$instance = $this;
		$this->autoloader = $autoloader;
		$this->logger = $logger;
		$this->network = new Network($this);
		$this->addInterface(new RakLibInterface($this));
		$this->start();
	}

	public function start() {
		if (function_exists("pcntl_signal")) {
			pcntl_signal(SIGTERM, [$this, "handleSignal"]);
			pcntl_signal(SIGINT, [$this, "handleSignal"]);
			pcntl_signal(SIGHUP, [$this, "handleSignal"]);
		}

		$this->tickCounter = 0;
		$this->tickAverage = array();
		$this->useAverage = array();
		for ($i = 0; $i < 1200; $i++) {
			$this->tickAverage[] = 20;
			$this->useAverage[] = 0;
		}
		$this->logger->info("Server started");
		$this->tickProcessor();
	}

	private function tickProcessor() {
		$this->nextTick = microtime(true);
		while ($this->isRunning) {
			$this->tick();
			$next = $this->nextTick - 0.0001;
			if ($next > microtime(true)) {
				@time_sleep_until($next);
			}
		}
		$this->forceShutdown();
	}

	private function tick() {
		$tickTime = microtime(true);
		if ($tickTime < $this->nextTick) {
			return false;
		}
		++$this->tickCounter;

		if (($this->tickCounter & 0b1111) === 0) {
			$this->titleTick();
		}
		if ($this->tickCounter % 5 == 0) {
			pcntl_signal_dispatch();			
		}
		
		$this->network->processInterfaces();
		$this->checkSockets();

		$now = microtime(true);
		array_shift($this->tickAverage);
		$this->tickAverage[] = min(20, 1 / max(0.001, $now - $tickTime));
		array_shift($this->useAverage);
		$this->useAverage[] = min(1, ($now - $tickTime) / 0.05);

		if (($this->nextTick - $tickTime) < -1) {
			$this->nextTick = $tickTime;
		}
		$this->nextTick += 0.05;

		return true;
	}

	private function titleTick() {
		if (\proxy\ANSI === true) {
			echo "\x1b]0;" . "Proxy Server | Online " . count($this->players) . "/" . $this->getMaxPlayers() . " | RAM " . round((memory_get_usage() / 1024) / 1024, 2) . "/" . round((memory_get_usage(true) / 1024) / 1024, 2) . " MB | U " . round($this->network->getUpload() / 1024, 2) . " D " . round($this->network->getDownload() / 1024, 2) . " kB/s | TPS " . $this->getTicksPerSecond() . " | Load " . $this->getTickUsage() . "%\x07";
		}
	}

	public function handleSignal($signo) {
		if ($signo === SIGTERM or $signo === SIGINT or $signo === SIGHUP) {
			$this->isRunning = false;
		}
	}

	private function forceShutdown() {
		try {
			foreach ($this->players as $player) {
				$player->close(TextFormat::YELLOW . $player->getName() . " has left the game", "Proxy server closed");
			}
			foreach ($this->network->getInterfaces() as $interface) {
				$interface->shutdown();
				$this->network->unregisterInterface($interface);
			}
		} catch (\Exception $e) {
			$this->logger->emergency("Crashed while crashing, killing process");
			@kill(getmypid());
		}
	}

	public function checkSockets() {
		foreach ($this->sockets as $socket) {
			$socket->checkMessages();
		}
	}

	public function createSockets($address, $port) {
		try {
			$socket = new ProxySocket($this, $address, $port);
			$this->sockets[$socket->getIdentifier()] = $socket;
			return $socket;
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage());
			return false;
		}
	}

	public function checkPacket($proxyIdentifier, $buffer, $type = self::STANDART_PACKET_ID) {
		if (isset($this->players[$proxyIdentifier])) {
			$player = $this->players[$proxyIdentifier];
			if ($type == self::STANDART_PACKET_ID) {
				$player->sendFromProxyPacket($buffer);
			} elseif ($type == self::PROXY_PACKET_ID) {
				$pk = $this->getProxyPacket($buffer);
				if ($pk === false) {
					return;
				}
				if (!is_null($pk)) {
					$pk->decode();
					$player->handleProxyDataPacket($pk);
				}
			}
		}
	}

	private function getProxyPacket($buffer) {
		$pid = ord($buffer{0});
		if (($data = $this->getNetwork()->getProxyPacket($pid)) === null) {
			return null;
		}
		$data->setBuffer($buffer, 1);
		return $data;
	}

	public function getSocket($address, $port) {
		if (isset($this->sockets[$address . $port])) {
			return $this->sockets[$address . $port];
		} else {
			return $this->createSockets($address, $port);
		}
	}

}

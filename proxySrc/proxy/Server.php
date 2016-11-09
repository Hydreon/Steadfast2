<?php

namespace proxy;

use proxy\network\Network;
use proxy\network\RakLibInterface;
use proxy\network\SourceInterface;
use proxy\utils\TextFormat;
use proxy\utils\Config;

class Server {

	const STANDART_PACKET_ID = 0x01;
	const PROXY_PACKET_ID = 0x02;
	const PLAYER_PACKET_ID = 0x03;
	const SYSTEM_PACKET_ID = 0x04;
	const SYSTEM_DATA_PACKET_ID = 0x05;

	public static $lastPlayerId = 1;
	private static $instance = null;
	private $isRunning = true;
	private $tickCounter;
	private $nextTick = 0;
	private $tickAverage = [];
	private $useAverage = [];
	private $logger;
	private $network;
	private $autoloader;
	private $players = [];
	private $sockets = [];
	private $waitSockets = [];
	private $properties;
	private $tps = 40;
	private $tickTime;
	private $lastTick = 0;
	private $shortData = '';
	private $longData = '';
	
	public $raklibInterface;

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
		return $this->getConfigInt("max-players", 100);
	}

	public function getPort() {
		return $this->getConfigInt("server-port", 19132);
	}

	public function getProxyPort() {
		return $this->getConfigInt("proxy-port", 10305);
	}

	public function getIp() {
		return $this->getConfigString("server-ip", "0.0.0.0");
	}

	public function getDefaultServer() {
		return $this->getConfigString("default-server", "0.0.0.0");
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
		$this->properties = new Config("./proxy.properties", Config::PROPERTIES, [
			"motd" => "Minecraft: PE Proxy Server",
			"server-port" => 19132,
			"server-ip" => '0.0.0.0',
			"proxy-port" => 10305,
			"max-players" => 200,
			"default-server" => '0.0.0.0'
		]);
		self::$instance = $this;
		$this->autoloader = $autoloader;
		$this->logger = $logger;
		$this->network = new Network($this);
		$this->raklibInterface = new RakLibInterface($this);
		$this->addInterface($this->raklibInterface);
		$this->start();
	}

	public function start() {
		if (function_exists("pcntl_signal")) {
			pcntl_signal(SIGTERM, [$this, "handleSignal"]);
			pcntl_signal(SIGINT, [$this, "handleSignal"]);
			pcntl_signal(SIGHUP, [$this, "handleSignal"]);
		}

		$this->tickTime = 1 / $this->tps;
		$this->tickCounter = 0;
		$this->tickAverage = array();
		$this->useAverage = array();
		for ($i = 0; $i < 1200; $i++) {
			$this->tickAverage[] = $this->tps;
			$this->useAverage[] = 0;
		}
		$this->logger->info("Server started on " . ($this->getIp() === "" ? "*" : $this->getIp()) . ":" . $this->getPort());
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
		
		if ($this->lastTick < time()) {
			if ($this->lastTick % 5 == 0) {
				if (($socket = $this->getDefaultSocket())) {
					$socket->writeMessage(chr(self::SYSTEM_DATA_PACKET_ID) . chr(0x01));
				}
			}			
			
			$this->lastTick = time();
			foreach ($this->waitSockets as $key => $socket) {
				try {
					if ($socket->checkConnect()) {
						$this->sockets[$socket->getIdentifier()] = $socket;
						unset($this->waitSockets[$key]);
					}
				} catch (\Exception $e) {
					$this->logger->warning($e->getMessage());
					unset($this->waitSockets[$key]);
				}
			}
		}

		$now = microtime(true);
		array_shift($this->tickAverage);
		$this->tickAverage[] = min($this->tps, 1 / max(0.001, $now - $tickTime));
		array_shift($this->useAverage);
		$this->useAverage[] = min(1, ($now - $tickTime) / $this->tickTime);

		if (($this->nextTick - $tickTime) < -1) {
			$this->nextTick = $tickTime;
		}
		$this->nextTick += $this->tickTime;

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
			$this->properties->save();
		} catch (\Exception $e) {
			$this->logger->emergency("Crashed while crashing, killing process");
			@kill(getmypid());
		}
	}

	public function checkSockets() {
		foreach ($this->sockets as $key => $socket) {
			if (!$socket->checkMessages()) {
				foreach ($this->players as $player) {
					if ($player->getSocket() == $socket) {
						$player->close('', 'Lost remote server');
					}
				}
				echo 'REMOVE SOCKET: ' . $key . PHP_EOL;
				unset($this->sockets[$key]);
			}
		}
	}

	public function createSockets($address, $port, $wait = false) {
		try {
			$socket = new ProxySocket($this, $address, $port, $wait);
			if ($wait) {
				$this->sockets[$socket->getIdentifier()] = $socket;
			}
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

	public function getConfigBoolean($variable, $defaultValue = false) {
		$v = getopt("", ["$variable::"]);
		if (isset($v[$variable])) {
			$value = $v[$variable];
		} else {
			$value = $this->properties->exists($variable) ? $this->properties->get($variable) : $defaultValue;
		}

		if (is_bool($value)) {
			return $value;
		}
		switch (strtolower($value)) {
			case "on":
			case "true":
			case "1":
			case "yes":
				return true;
		}

		return false;
	}

	public function getConfigInt($variable, $defaultValue = 0) {
		$v = getopt("", ["$variable::"]);
		if (isset($v[$variable])) {
			return (int) $v[$variable];
		}

		return $this->properties->exists($variable) ? (int) $this->properties->get($variable) : (int) $defaultValue;
	}

	public function getConfigString($variable, $defaultValue = "") {
		$v = getopt("", ["$variable::"]);
		if (isset($v[$variable])) {
			return (string) $v[$variable];
		}

		return $this->properties->exists($variable) ? $this->properties->get($variable) : $defaultValue;
	}

	public function handlePacket($packet) {
		$socket = $this->getDefaultSocket();
		if ($socket !== false) {
			$socket->writeMessage(chr(static::SYSTEM_PACKET_ID) . $packet);
		}
	}

	public function sendRawPacket($address, $port, $payload) {
		$this->network->sendPacket($address, $port, $payload);
	}

	public function getDefaultSocket() {
		$address = $this->getDefaultServer();
		$port = $this->getProxyPort();
		if (isset($this->sockets[$address . $port])) {
			return $this->sockets[$address . $port];
		} else {
			echo 'CREATE DEFAULT SOCKET: ' . $address . ' : ' . $port . PHP_EOL;
			return $this->createSockets($address, $port, true);
		}
	}

	public function checkRedirect($address, $port, $player) {
		$address = gethostbyname($address);
		if (isset($this->sockets[$address . $port])) {
			$player->changeServer($this->sockets[$address . $port]);
		} elseif (isset($this->waitSockets[$address . $port])) {
			$this->waitSockets[$address . $port]->addWaitPlayer($player);
		} else {
			echo 'CREATE SOCKET: ' . $address . ' : ' . $port . PHP_EOL;
			$socket = $this->createSockets($address, $port, false);
			if ($socket) {
				$socket->addWaitPlayer($player);
				$this->waitSockets[$address . $port] = $socket;
			}
		}
	}
	
	public function setLongData($data) {
		$this->longData = $data;
	}
	
	public function setShortData($data) {
		$this->shortData = $data;
	}
	
	public function getLongData() {
		return $this->longData;
	}
	
	public function getShortData() {
		return $this->shortData;
	}
	
	

}

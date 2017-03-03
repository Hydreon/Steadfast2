<?php

namespace pocketmine\network\proxylib;

use pocketmine\Worker;

class ProxyServer extends Worker {

	private $logger;
	private $interface;
	private $port;
	private $shutdown;
	private $socket;
	private $remoteProxyServerManager;
	private $externalQueue;
	private $internalQueue;

	public function __construct(\ThreadedLogger $logger, \ClassLoader $loader, $port = 10305, $interface = "0.0.0.0") {
		$this->logger = $logger;
		$this->interface = $interface;
		$this->port = (int) $port;
		$this->shutdown = false;
		$this->setClassLoader($loader);
		$this->externalQueue = new \Threaded;
		$this->internalQueue = new \Threaded;
		$this->start();
	}

	public function run() {
		$this->registerClassLoader();
		gc_enable();
		ini_set("memory_limit", -1);
		ini_set("display_errors", 1);
		ini_set("display_startup_errors", 1);

		set_error_handler([$this, "errorHandler"], E_ALL);
		register_shutdown_function([$this, "shutdownHandler"]);


		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (socket_bind($this->socket, $this->interface, $this->port) !== true) {
			$this->logger->critical("FAILED TO BIND TO " . $this->interface . ":" . $this->port . "!");
			exit(1);
		}	
		socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 64);
 		socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 64);
		socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, ["l_onoff" => 1, "l_linger" => 0]);
		socket_listen($this->socket, 20);
		$this->logger->info("ProxyServer is running on $this->interface:$this->port");
		socket_set_nonblock($this->socket);
		$this->remoteProxyServerManager = new RemoteProxyServerManager($this);
		$this->remoteProxyServerManager->tickProcessor();
		socket_close($this->socket);
		var_dump("ProxyServer thread shutdown!");
	}

	public function getNewServer() {
		return socket_accept($this->socket);
	}
	
	public function readFromProxyServer() {
		return $this->externalQueue->shift();
	}

	public function pushToExternalQueue($data) {
		$this->externalQueue[] = $data;
	}

	public function writeToProxyServer($data) {
		$this->internalQueue[] = $data;
	}

	public function readFromInternaQueue() {
		return $this->internalQueue->shift();
	}

	public function getLogger() {
		return $this->logger;
	}

	public function isShutdown() {
		return $this->shutdown;
	}

	public function shutdown() {
		$this->shutdown = true;
	}

	public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null) {
		if (error_reporting() == 0) {
			return;
		}
		$errorConversion = [
			E_ERROR => "E_ERROR",
			E_WARNING => "E_WARNING",
			E_PARSE => "E_PARSE",
			E_NOTICE => "E_NOTICE",
			E_CORE_ERROR => "E_CORE_ERROR",
			E_CORE_WARNING => "E_CORE_WARNING",
			E_COMPILE_ERROR => "E_COMPILE_ERROR",
			E_COMPILE_WARNING => "E_COMPILE_WARNING",
			E_USER_ERROR => "E_USER_ERROR",
			E_USER_WARNING => "E_USER_WARNING",
			E_USER_NOTICE => "E_USER_NOTICE",
			E_STRICT => "E_STRICT",
			E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
			E_DEPRECATED => "E_DEPRECATED",
			E_USER_DEPRECATED => "E_USER_DEPRECATED",
		];

		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if (($pos = strpos($errstr, "\n")) !== false) {
			$errstr = substr($errstr, 0, $pos);
		}

		var_dump("An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline");
//		@file_put_contents('logs/' .date('Y.m.d') . '_debug.log', "An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline\n", FILE_APPEND | LOCK_EX);

		foreach (($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
			var_dump($line);
//			@file_put_contents('logs/' .date('Y.m.d') . '_debug.log', $line."\n", FILE_APPEND | LOCK_EX);
		}

		return true;
	}

	public function getTrace($start = 1, $trace = null) {
		if ($trace === null) {
			if (function_exists("xdebug_get_function_stack")) {
				$trace = array_reverse(xdebug_get_function_stack());
			} else {
				$e = new \Exception();
				$trace = $e->getTrace();
			}
		}

		$messages = [];
		$j = 0;
		for ($i = (int) $start; isset($trace[$i]); ++$i, ++$j) {
			$params = "";
			if (isset($trace[$i]["args"]) or isset($trace[$i]["params"])) {
				if (isset($trace[$i]["args"])) {
					$args = $trace[$i]["args"];
				} else {
					$args = $trace[$i]["params"];
				}
				foreach ($args as $name => $value) {
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? ($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}

		return $messages;
	}

	public function shutdownHandler() {
		if ($this->shutdown !== true) {
			var_dump("ProxyServer thread crashed!");
		}
		socket_close($this->socket);
	}

}

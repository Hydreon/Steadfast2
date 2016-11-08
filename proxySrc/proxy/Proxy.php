<?php

namespace proxy {

	use proxy\utils\MainLogger;
	use proxy\utils\Terminal;
	use proxy\utils\Utils;

	const MINECRAFT_VERSION_NETWORK = "0.15.0";

	if (\Phar::running(true) !== "") {
		@define("proxy\\PATH", \Phar::running(true) . "/");
	} else {
		@define("proxy\\PATH", getcwd() . DIRECTORY_SEPARATOR);
	}

	if (version_compare("7.0", PHP_VERSION) > 0) {
		echo "[CRITICAL] You must use PHP >= 7.0" . PHP_EOL;
		echo "[CRITICAL] Please use the installer provided on the homepage." . PHP_EOL;
		exit(1);
	}


	if (!extension_loaded("pthreads")) {
		echo "[CRITICAL] Unable to find the pthreads extension." . PHP_EOL;
		echo "[CRITICAL] Please use the installer provided on the homepage." . PHP_EOL;
		exit(1);
	}

	if (!class_exists("ClassLoader", false)) {
		require_once(\proxy\PATH . "proxySrc/spl/ClassLoader.php");
		require_once(\proxy\PATH . "proxySrc/spl/BaseClassLoader.php");
	}


	if (!class_exists("ClassLoader", false)) {
		require_once(\proxy\PATH . "proxySrc/spl/ClassLoader.php");
		require_once(\proxy\PATH . "proxySrc/spl/BaseClassLoader.php");
	}

	$autoloader = new \BaseClassLoader();
	$autoloader->addPath(\proxy\PATH . "proxySrc");
	require_once(\proxy\PATH . "proxySrc/proxy/utils/Utils.php");
	$autoloader->addPath(\proxy\PATH . "proxySrc" . DIRECTORY_SEPARATOR . "spl");
	$autoloader->register(true);

	set_time_limit(0);
	gc_enable();
	error_reporting(-1);
	ini_set("allow_url_fopen", 1);
	ini_set("display_errors", 1);
	ini_set("display_startup_errors", 1);
	ini_set("default_charset", "utf-8");
	ini_set("memory_limit", -1);

	define("proxy\\START_TIME", microtime(true));

	Terminal::init();

	define("proxy\\ANSI", Terminal::hasFormattingCodes());
	date_default_timezone_set("UTC");



	$logger = new MainLogger("server.log");



	$errors = 0;

	if (php_sapi_name() !== "cli") {
		$logger->critical("You must run using the CLI.");
		++$errors;
	}

	if (!extension_loaded("sockets")) {
		$logger->critical("Unable to find the Socket extension.");
		++$errors;
	}

	$pthreads_version = phpversion("pthreads");
	if (substr_count($pthreads_version, ".") < 2) {
		$pthreads_version = "0.$pthreads_version";
	}
	if (version_compare($pthreads_version, "3.0.7") < 0) {
		$logger->critical("pthreads >= 3.0.7 is required, while you have $pthreads_version.");
		++$errors;
	}

	if (!extension_loaded("curl")) {
		$logger->critical("Unable to find the cURL extension.");
		++$errors;
	}

	if (!extension_loaded("yaml")) {
		$logger->critical("Unable to find the YAML extension.");
		++$errors;
	}

	if (!extension_loaded("sqlite3")) {
		$logger->critical("Unable to find the SQLite3 extension.");
		++$errors;
	}

	if (!extension_loaded("zlib")) {
		$logger->critical("Unable to find the Zlib extension.");
		++$errors;
	}

	if ($errors > 0) {
		$logger->critical("Please use the installer provided on the homepage, or recompile PHP again.");
		$logger->shutdown();
		$logger->join();
		exit(1);
	}

	if (\Phar::running(true) === "") {
		$logger->warning("Non-packaged installation detected, do not use on production.");
	}

	function getTrace($start = 1, $trace = null) {
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
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . (is_array($value) ? "Array()" : Utils::printable(@strval($value)))) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? cleanPath($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . Utils::printable(substr($params, 0, -2)) . ")";
		}

		return $messages;
	}

	function cleanPath($path) {
		return rtrim(str_replace(["\\", ".php", "phar://"], ["/", "", ""], $path), "/");
	}

	function kill($pid) {
		switch (Utils::getOS()) {
			case "win":
				exec("taskkill.exe /F /PID " . ((int) $pid) . " > NUL");
				break;
			case "mac":
			case "linux":
			default:
				exec("kill -9 " . ((int) $pid) . " > /dev/null 2>&1");
		}
	}

	$server = new Server($autoloader, $logger);


	$logger->info("Server closed");
	$logger->shutdown();

	exit(0);
}

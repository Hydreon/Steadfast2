<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine;

/**
 * This class must be extended by all custom threading classes
 */
abstract class Thread extends \Thread {

	/** @var \ClassLoader */
	protected $classLoader;
	protected $isKilled = false;

	public function getClassLoader() {
		return $this->classLoader;
	}

	public function setClassLoader(\ClassLoader $loader = null) {
		if ($loader === null) {
			$loader = Server::getInstance()->getLoader();
		}
		$this->classLoader = $loader;
	}

	public function registerClassLoader() {
		if (!interface_exists("ClassLoader", false)) {
			require(\pocketmine\PATH . "src/spl/ClassLoader.php");
			require(\pocketmine\PATH . "src/spl/BaseClassLoader.php");
			require(\pocketmine\PATH . "src/pocketmine/CompatibleClassLoader.php");
		}
		if ($this->classLoader !== null) {
			$this->classLoader->register(true);
		}
	}

	public function start(int $options = PTHREADS_INHERIT_ALL) {
		ThreadManager::getInstance()->add($this);

		if (!$this->isRunning() and ! $this->isJoined() and ! $this->isTerminated()) {
			if ($this->getClassLoader() === null) {
				$this->setClassLoader();
			}
			return parent::start($options);
		}

		return false;
	}

	/**
	 * Stops the thread using the best way possible. Try to stop it yourself before calling this.
	 */
	public function quit() {
		$this->isKilled = true;
		$this->notify();
		if (!$this->isJoined()) {
			if (!$this->isTerminated()) {
				$this->join();
			}
		}

		ThreadManager::getInstance()->remove($this);
	}

	public function getThreadName() {
		return (new \ReflectionClass($this))->getShortName();
	}

	public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null) {
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
		@file_put_contents('logs/' . date('Y.m.d') . '_debug.log', "An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline\n", FILE_APPEND | LOCK_EX);

		foreach (($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line) {
			@file_put_contents('logs/' . date('Y.m.d') . '_debug.log', $line . "\n", FILE_APPEND | LOCK_EX);
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

}

<?php

namespace proxy\utils;

use LogLevel;

class MainLogger extends \AttachableThreadedLogger {

	protected $logFile;
	protected $logStream;
	protected $shutdown;
	protected $logDebug;
	public static $logger = null;

	public function __construct($logFile, $logDebug = false) {
		if (static::$logger instanceof MainLogger) {
			throw new \RuntimeException("MainLogger has been already created");
		}
		static::$logger = $this;
		$this->logStream = new \Threaded;
		$this->start();
	}

	public static function getLogger() {
		return static::$logger;
	}

	public function emergency($message) {
		$this->send($message, \LogLevel::EMERGENCY, "EMERGENCY", TextFormat::RED);
	}

	public function alert($message) {
		$this->send($message, \LogLevel::ALERT, "ALERT", TextFormat::RED);
	}

	public function critical($message) {
		$this->send($message, \LogLevel::CRITICAL, "CRITICAL", TextFormat::RED);
	}

	public function error($message) {
		$this->send($message, \LogLevel::ERROR, "ERROR", TextFormat::DARK_RED);
	}

	public function warning($message) {
		$this->send($message, \LogLevel::WARNING, "WARNING", TextFormat::YELLOW);
	}

	public function notice($message) {
		$this->send($message, \LogLevel::NOTICE, "NOTICE", TextFormat::AQUA);
	}

	public function info($message) {
		$this->send($message, \LogLevel::INFO, "INFO", TextFormat::WHITE);
	}

	public function debug($message) {
		if ($this->logDebug === false) {
			return;
		}
		$this->send($message, \LogLevel::DEBUG, "DEBUG", TextFormat::GRAY);
	}

	public function setLogDebug($logDebug) {
		$this->logDebug = (bool) $logDebug;
	}

	public function logException(\Throwable $e, $trace = null) {
		if ($trace === null) {
			$trace = $e->getTrace();
		}
		$errstr = $e->getMessage();
		$errfile = $e->getFile();
		$errno = $e->getCode();
		$errline = $e->getLine();

		$errorConversion = [
			0 => "EXCEPTION",
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
		if ($errno === 0) {
			$type = LogLevel::CRITICAL;
		} else {
			$type = ($errno === E_ERROR or $errno === E_USER_ERROR) ? LogLevel::ERROR : (($errno === E_USER_WARNING or $errno === E_WARNING) ? LogLevel::WARNING : LogLevel::NOTICE);
		}
		$errno = isset($errorConversion[$errno]) ? $errorConversion[$errno] : $errno;
		if (($pos = strpos($errstr, "\n")) !== false) {
			$errstr = substr($errstr, 0, $pos);
		}
		$errfile = \proxy\cleanPath($errfile);
		$this->log($type, get_class($e) . ": \"$errstr\" ($errno) in \"$errfile\" at line $errline");
		foreach (@\proxy\getTrace(1, $trace) as $i => $line) {
			$this->debug($line);
		}
	}

	public function log($level, $message) {
		switch ($level) {
			case LogLevel::EMERGENCY:
				$this->emergency($message);
				break;
			case LogLevel::ALERT:
				$this->alert($message);
				break;
			case LogLevel::CRITICAL:
				$this->critical($message);
				break;
			case LogLevel::ERROR:
				$this->error($message);
				break;
			case LogLevel::WARNING:
				$this->warning($message);
				break;
			case LogLevel::NOTICE:
				$this->notice($message);
				break;
			case LogLevel::INFO:
				$this->info($message);
				break;
			case LogLevel::DEBUG:
				$this->debug($message);
				break;
		}
	}

	public function shutdown() {
		$this->shutdown = true;
	}

	protected function send($message, $level, $prefix, $color) {
		$now = time();

		$thread = \Thread::getCurrentThread();
		if ($thread === null) {
			$threadName = "Server thread";
		} else {
			$threadName = (new \ReflectionClass($thread))->getShortName() . " thread";
		}

		$message = TextFormat::toANSI(TextFormat::AQUA . "[" . date("H:i:s", $now) . "] " . TextFormat::RESET . $color . "[" . $threadName . "/" . $prefix . "]:" . " " . $message . TextFormat::RESET);
		$cleanMessage = TextFormat::clean($message);

		if (!Terminal::hasFormattingCodes()) {
			echo $cleanMessage . PHP_EOL;
		} else {
			echo $message . PHP_EOL;
		}

		if ($this->attachment instanceof \ThreadedLoggerAttachment) {
			$this->attachment->call($level, $message);
		}

		$this->logStream[] = date("Y-m-d", $now) . " " . $cleanMessage . "\n";
		if ($this->logStream->count() === 1) {
			$this->synchronized(function() {
				$this->notify();
			});
		}
	}

	public function run() {
		$this->shutdown = false;
	}

}

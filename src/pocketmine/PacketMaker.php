<?php

namespace pocketmine;

use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use pocketmine\network\CachedEncapsulatedPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\utils\Binary;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;

class PacketMaker extends Worker {


	protected $classLoader;
	protected $shutdown;
	
	protected $externalQueue;
	protected $internalQueue;	

	public function __construct(\ClassLoader $loader = null) {
		$this->externalQueue = new \Threaded;
		$this->internalQueue = new \Threaded;
		$this->shutdown = false;
		$this->classLoader = $loader;
		$this->start(PTHREADS_INHERIT_CONSTANTS);
	}
	
	
	public function registerClassLoader(){
		if(!interface_exists("ClassLoader", false)){
			require(\pocketmine\PATH . "src/spl/ClassLoader.php");
			require(\pocketmine\PATH . "src/spl/BaseClassLoader.php");
			require(\pocketmine\PATH . "src/pocketmine/CompatibleClassLoader.php");
		}
		if($this->classLoader !== null){
			$this->classLoader->register(true);
		}
	}
	
	public function run() {
		$this->registerClassLoader();
		gc_enable();
		ini_set("memory_limit", -1);
		ini_set("display_errors", 1);
		ini_set("display_startup_errors", 1);

		set_error_handler([$this, "errorHandler"], E_ALL);
		register_shutdown_function([$this, "shutdownHandler"]);
		$this->tickProcessor();
	}

	public function pushMainToThreadPacket($data) {
		$this->internalQueue[] = $data;
	}

	public function readMainToThreadPacket() {
		return $this->internalQueue->shift();
	}
	public function readThreadToMainPacket() {
		return $this->externalQueue->shift();
	}

	protected function tickProcessor() {
		while (!$this->shutdown) {			
			$start = microtime(true);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.024) {
				time_sleep_until(microtime(true) + 0.025 - $time);
			}
		}
	}

	protected function tick() {				
		while(count($this->internalQueue) > 0){
			$data = unserialize($this->readMainToThreadPacket());
			$this->checkPacket($data);
		}
	}
	
	protected function checkPacket($data) {
		if (isset($data['moveData'])) {
			foreach ($data['moveData'] as $identifier => $moveData) {
				$moveStr = "";
				foreach ($moveData['data'] as $singleMoveData) {
					$pk = new MoveEntityPacket($moveData['additionalChar']);
					$pk->entities = [$singleMoveData];
					$pk->encode();
					$pk->updateBuffer($moveData['additionalChar']);
					$moveStr .= Binary::writeInt(strlen($pk->buffer)) . $pk->buffer;					
				}
				$buffer = zlib_encode($moveStr, ZLIB_ENCODING_DEFLATE, 7);
				$pkBatch = new BatchPacket();
				$pkBatch->payload = $buffer;
				$pkBatch->encode();
				$pkBatch->isEncoded = true;
				$this->externalQueue[] = $this->makeBuffer($identifier, $moveData['additionalChar'], $pkBatch, false, false);
			}	
			foreach ($data['motionData'] as $identifier => $motionData) {
				$motionStr = "";
				foreach ($motionData['data'] as $singleMotionData) {
					$pk = new SetEntityMotionPacket($motionData['additionalChar']);
					$pk->entities = [$singleMotionData];
					$pk->encode();
					$pk->updateBuffer($motionData['additionalChar']);
					$motionStr .= Binary::writeInt(strlen($pk->buffer)) . $pk->buffer;		
				}
				$buffer = zlib_encode($motionStr, ZLIB_ENCODING_DEFLATE, 7);
				$pkBatch = new BatchPacket();
				$pkBatch->payload = $buffer;
				$pkBatch->encode();
				$pkBatch->isEncoded = true;
				$this->externalQueue[] = $this->makeBuffer($identifier, $motionData['additionalChar'], $pkBatch, false, false);
			}
		} elseif($data['isBatch']) {
			$str = "";
			$str15 = "";
			foreach($data['packets'] as $p){
				if($p instanceof DataPacket){
					if(!$p->isEncoded){					
						$p->encode();
					}
					$str .= Binary::writeInt(strlen($p->buffer)) . $p->buffer;
					$p->updateBuffer(chr(0xfe));
					$str15 .= Binary::writeInt(strlen($p->buffer)) . $p->buffer;
				}else{					
					$str .= Binary::writeInt(strlen($p)) . $p;
					$pkId = ord($p{0});
					$p{0} = chr(DataPacket::$pkKeysRev[$pkId]);
					$str15 .= Binary::writeInt(strlen($p)) . $p;
				}
			}
			$buffer = zlib_encode($str, ZLIB_ENCODING_DEFLATE, $data['networkCompressionLevel']);
			$buffer15 = zlib_encode($str15, ZLIB_ENCODING_DEFLATE, $data['networkCompressionLevel']);
			$pk = new BatchPacket();
			$pk->payload = $buffer;
			$pk->encode();
			$pk->isEncoded = true;
			
			$pk15 = new BatchPacket();
			$pk15->payload = $buffer15;
			$pk15->encode();
			$pk15->isEncoded = true;
			foreach($data['targets'] as $target){
				$this->externalQueue[] = $this->makeBuffer($target[0], $target[1], ($target[1] == chr(0xfe) ? $pk15 : $pk), false, false);
			}
		}
		
	}

	protected function makeBuffer($identifier, $additionalChar, $fullPacket, $needACK, $identifierACK) {		
		$pk = null;
		if (!$fullPacket->isEncoded) {
			$fullPacket->encode();
		} elseif (!$needACK) {
			if (isset($fullPacket->__encapsulatedPacket)) {
				unset($fullPacket->__encapsulatedPacket);
			}
			$fullPacket->updateBuffer($additionalChar);
			$fullPacket->__encapsulatedPacket = new CachedEncapsulatedPacket();
			$fullPacket->__encapsulatedPacket->identifierACK = null;
			$fullPacket->__encapsulatedPacket->buffer = $additionalChar . $fullPacket->buffer;
			$fullPacket->__encapsulatedPacket->reliability = 2;
			$pk = $fullPacket->__encapsulatedPacket;
		}

		if ($pk === null) {
			$fullPacket->updateBuffer($additionalChar);
			$pk = new EncapsulatedPacket();			
			$pk->buffer = $additionalChar . $fullPacket->buffer;
			$pk->reliability = 2;

			if ($needACK === true && $identifierACK !== false) {
				$pk->identifierACK = $identifierACK;
			}
		}

		$flags = ($needACK === true ? RakLib::FLAG_NEED_ACK : RakLib::PRIORITY_NORMAL) | (RakLib::PRIORITY_NORMAL);

		$buffer = chr(RakLib::PACKET_ENCAPSULATED) . chr(strlen($identifier)) . $identifier . chr($flags) . $pk->toBinary(true);

		return $buffer;
	}
	
	public function shutdown(){		
		$this->shutdown = true;
		var_dump("Packet thread shutdown!");
	}
	
	
	public function errorHandler($errno, $errstr, $errfile, $errline, $context, $trace = null){
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
		if(($pos = strpos($errstr, "\n")) !== false){
			$errstr = substr($errstr, 0, $pos);
		}

		//var_dump("An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline");	
		@file_put_contents('logs/' .date('Y.m.d') . '_debug.log', "An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline\n", FILE_APPEND | LOCK_EX);

		foreach(($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line){
			//var_dump($line);			
			@file_put_contents('logs/' .date('Y.m.d') . '_debug.log', $line."\n", FILE_APPEND | LOCK_EX);
		}

		return true;
	}
	
	
	public function getTrace($start = 1, $trace = null){
		if($trace === null){
			if(function_exists("xdebug_get_function_stack")){
				$trace = array_reverse(xdebug_get_function_stack());
			}else{
				$e = new \Exception();
				$trace = $e->getTrace();
			}
		}

		$messages = [];
		$j = 0;
		for($i = (int) $start; isset($trace[$i]); ++$i, ++$j){
			$params = "";
			if(isset($trace[$i]["args"]) or isset($trace[$i]["params"])){
				if(isset($trace[$i]["args"])){
					$args = $trace[$i]["args"];
				}else{
					$args = $trace[$i]["params"];
				}
				foreach($args as $name => $value){
					$params .= (is_object($value) ? get_class($value) . " " . (method_exists($value, "__toString") ? $value->__toString() : "object") : gettype($value) . " " . @strval($value)) . ", ";
				}
			}
			$messages[] = "#$j " . (isset($trace[$i]["file"]) ? ($trace[$i]["file"]) : "") . "(" . (isset($trace[$i]["line"]) ? $trace[$i]["line"] : "") . "): " . (isset($trace[$i]["class"]) ? $trace[$i]["class"] . (($trace[$i]["type"] === "dynamic" or $trace[$i]["type"] === "->") ? "->" : "::") : "") . $trace[$i]["function"] . "(" . substr($params, 0, -2) . ")";
		}

		return $messages;
	}
	
	public function shutdownHandler(){
		if($this->shutdown !== true){
			var_dump("Packet thread crashed!");
		}
	}

}

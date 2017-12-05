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
use pocketmine\network\protocol\MovePlayerPacket;

class PacketMaker extends Thread {


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
		DataPacket::initPackets();
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
				@time_sleep_until(microtime(true) + 0.025 - $time);
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
					if ($singleMoveData[7]) {
						$pk = new MovePlayerPacket();
						$pk->eid = $singleMoveData[0];
						$pk->x = $singleMoveData[1];
						$pk->y = $singleMoveData[2];
						$pk->z = $singleMoveData[3];
						$pk->pitch = $singleMoveData[6];
						$pk->yaw = $singleMoveData[5];
						$pk->bodyYaw = $singleMoveData[4];
					} else {
						$pk = new MoveEntityPacket();
						$pk->entities = [$singleMoveData];
					}
					$pk->senderSubClientID = $singleMoveData[8];
					$pk->encode($moveData['playerProtocol']);
					$moveStr .= Binary::writeVarInt(strlen($pk->buffer)) . $pk->buffer;					
				}
				$buffer = zlib_encode($moveStr, ZLIB_ENCODING_DEFLATE, 7);
				$pkBatch = new BatchPacket();
				$pkBatch->payload = $buffer;
				$pkBatch->encode($moveData['playerProtocol']);
				$pkBatch->isEncoded = true;
				$this->externalQueue[] = $this->makeBuffer($identifier, $pkBatch, false, false);
			}	
			foreach ($data['motionData'] as $identifier => $motionData) {
				$motionStr = "";
				foreach ($motionData['data'] as $singleMotionData) {
					$pk = new SetEntityMotionPacket();
					$pk->entities = [$singleMotionData];
					$pk->senderSubClientID = $singleMotionData[4];
					$pk->encode($motionData['playerProtocol']);
					$motionStr .= Binary::writeVarInt(strlen($pk->buffer)) . $pk->buffer;		
				}
				$buffer = zlib_encode($motionStr, ZLIB_ENCODING_DEFLATE, 7);
				$pkBatch = new BatchPacket();
				$pkBatch->payload = $buffer;
				$pkBatch->encode($motionData['playerProtocol']);
				$pkBatch->isEncoded = true;
				$this->externalQueue[] = $this->makeBuffer($identifier, $pkBatch, false, false);
			}
		} elseif($data['isBatch']) {
			$packetsStr = [];
			foreach($data['packets'] as $protocol => $packetData){		
				foreach ($packetData as $p) {
					if (!isset($packetsStr[$protocol])) {
						$packetsStr[$protocol] = "";
					}
					$packetsStr[$protocol] .= Binary::writeVarInt(strlen($p)) . $p;
				}
			}
			
			$packs = [];
			foreach ($packetsStr as $protocol => $str) {
				$buffer = zlib_encode($str, ZLIB_ENCODING_DEFLATE, $data['networkCompressionLevel']);
				$pk = new BatchPacket();
				$pk->payload = $buffer;
				$pk->encode($protocol);
				$pk->isEncoded = true;
				$packs[$protocol] = $pk;
			}
			
			foreach($data['targets'] as $target){
				if (isset($packs[$target[1]])) {
					$this->externalQueue[] = $this->makeBuffer($target[0], $packs[$target[1]], false, false);
				}
			}
		}
		
	}


	protected function makeBuffer($identifier, $fullPacket, $needACK, $identifierACK) {		
		$data = array(
			'identifier' => $identifier,
			'buffer' => $fullPacket->buffer
		);
		return serialize($data);
	}
	
	public function join() {
		$this->shutdown = true;
		parent::join();
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

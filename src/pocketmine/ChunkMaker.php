<?php

namespace pocketmine;

use pocketmine\utils\Binary;
use pocketmine\network\protocol\FullChunkDataPacket;

class ChunkMaker extends Worker {


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
			$count = count($this->internalQueue);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.025) {
				time_sleep_until(microtime(true) + 0.025 - $time);
			}
		}
	}

	protected function tick() {				
		while(count($this->internalQueue) > 0){
			$data = unserialize($this->readMainToThreadPacket());
			$this->doChunk($data);
		}
	}
	
	protected function doChunk($data) {
		$offset = 8;
		$blockIdArray = substr($data['chunk'], $offset, 32768);
		$offset += 32768;
		$blockDataArray = substr($data['chunk'], $offset, 16384);
		$offset += 16384;
		$skyLightArray = substr($data['chunk'], $offset, 16384);
		$offset += 16384;
		$blockLightArray = substr($data['chunk'], $offset, 16384);
		$offset += 16384;
		$heightMapArray = array_values(unpack("C*", substr($data['chunk'], $offset, 256)));
		$offset += 256;
		$biomeColorArray = array_values(unpack("N*", substr($data['chunk'], $offset, 1024)));

		$languages = ['English', 'German', 'Spanish'];
		
		$chunkDataWithoutSigns = $blockIdArray .
				$blockDataArray .
				$skyLightArray .
				$blockLightArray .
				pack("C*", ...$heightMapArray) .
				pack("N*", ...$biomeColorArray) .
				Binary::writeLInt(0) .
				$data['tiles'];		
		
		$result = array();
		$result['chunkX'] = $data['chunkX'];
		$result['chunkZ'] = $data['chunkZ'];
		foreach ($languages as $lang) {
			if(!isset($data['signTiles'][$lang])) {
				continue;
			}
			$chunkData = $chunkDataWithoutSigns . $data['signTiles'][$lang];
			$pk = new FullChunkDataPacket();
			$pk->chunkX = $data['chunkX'];
			$pk->chunkZ = $data['chunkZ'];
			$pk->order = FullChunkDataPacket::ORDER_COLUMNS;
			$pk->data = $chunkData;
			$pk->encode();
			if(!empty($pk->buffer)) {
				$str = Binary::writeInt(strlen($pk->buffer)) . $pk->buffer;
				$ordered = zlib_encode($str, ZLIB_ENCODING_DEFLATE, 7);
				$pk->updateBuffer(chr(0xfe));
				$str = Binary::writeInt(strlen($pk->buffer)) . $pk->buffer;
				$ordered15 = zlib_encode($str, ZLIB_ENCODING_DEFLATE, 7);			
				$result[$lang]['result'] = $ordered;
				$result[$lang]['result15'] = $ordered15;				
			}
		}
		$this->externalQueue[] = serialize($result);		
	}

	
	
	public function shutdown(){		
		$this->shutdown = true;
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
		
		var_dump("An $errno error happened: \"$errstr\" in \"$errfile\" at line $errline");		

		foreach(($trace = $this->getTrace($trace === null ? 3 : 0, $trace)) as $i => $line){
			var_dump($line);
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

}
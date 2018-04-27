<?php

namespace pocketmine;

use pocketmine\utils\Binary;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;

class ChunkMaker extends Thread {


	protected $classLoader;
	protected $shutdown;
	
	protected $externalQueue;
	protected $internalQueue;
	
	const SUPPORTED_PROTOCOL = [Info::BASE_PROTOCOL, Info::PROTOCOL_105, Info::PROTOCOL_110, Info::PROTOCOL_120, Info::PROTOCOL_200, Info::PROTOCOL_220, Info::PROTOCOL_221, Info::PROTOCOL_240, Info::PROTOCOL_260];

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
			$count = count($this->internalQueue);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.025) {
				@time_sleep_until(microtime(true) + 0.025 - $time);
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
		$chunkData120 = '';
		if (isset($data['isAnvil']) && $data['isAnvil'] == true) {
			$chunkData = chr(count($data['chunk']['sections']));
			$chunkData120 = chr(count($data['chunk']['sections']));
			foreach ($data['chunk']['sections'] as $y => $sections) {
				$chunkData .= chr(0);
				$chunkData120 .= chr(0);
				if ($sections['empty'] == true) {
					$chunkData .= str_repeat("\x00", 10240);
					$chunkData120 .= str_repeat("\x00", 6144);
				} else {
					if (isset($data['isSorted']) && $data['isSorted'] == true) {
						$blockData = $sections['blocks'] . $sections['data'];
						$lightData = $sections['skyLight'] . $sections['blockLight'];
					} else {
						$blockData = $this->sortData($sections['blocks']) . $this->sortHalfData($sections['data']);
						$lightData = $this->sortHalfData($sections['skyLight']) . $this->sortHalfData($sections['blockLight']);
					}
					$chunkData .= $blockData . $lightData;
					$chunkData120 .= $blockData;
				}
			}
			$chunkData .= $data['chunk']['heightMap'] .
					$data['chunk']['biomeColor'] .
					Binary::writeLInt(0) .
					$data['tiles'];		
			$chunkData120 .= $data['chunk']['heightMap'] .
					$data['chunk']['biomeColor'] .
					Binary::writeLInt(0) .
					$data['tiles'];
		} else {
			$blockIdArray = $data['blocks'];	
			$blockDataArray = $data['data'];
			$skyLightArray = $data['skyLight'];	
			$blockLightArray = $data['blockLight'];

			$countBlocksInChunk = 8;
			$chunkData = chr($countBlocksInChunk);		
			$chunkData120 = chr($countBlocksInChunk);		
			
			for ($blockIndex = 0; $blockIndex < $countBlocksInChunk; $blockIndex++) {
				$blockIdData = '';
				$blockDataData = '';
				$skyLightData = '';
				$blockLightData = '';
				for ($i = 0; $i < 256; $i++) {
//					$startIndex = $blockIndex * 8 + $i * 64;
					$startIndex = ($blockIndex + ($i << 3)) << 3;
					$blockIdData .= substr($blockIdArray, $startIndex << 1, 16);
					$blockDataData .= substr($blockDataArray, $startIndex, 8);
					$skyLightData .= substr($skyLightArray, $startIndex, 8);
					$blockLightData .= substr($blockLightArray, $startIndex, 8);
				}
				
				$chunkData .= chr(0) . $blockIdData . $blockDataData . $skyLightData . $blockLightData;
				$chunkData120 .= chr(0) . $blockIdData . $blockDataData;
			}


			$chunkData .= $data['heightMap'] .
					$data['biomeColor'] .
					Binary::writeLInt(0) .
					$data['tiles'];		
			$chunkData120 .= $data['heightMap'] .
					$data['biomeColor'] .
					Binary::writeLInt(0) .
					$data['tiles'];
		}
		
		$result = array();
		$result['chunkX'] = $data['chunkX'];
		$result['chunkZ'] = $data['chunkZ'];
		$protocols = isset($data['protocols']) ? $data['protocols'] : self::SUPPORTED_PROTOCOL;
		$subClientsId = isset($data['subClientsId']) ? $data['subClientsId'] : [ 0 ];
		foreach ($protocols as $protocol) {
			$pk = new FullChunkDataPacket();
			$pk->chunkX = $data['chunkX'];
			$pk->chunkZ = $data['chunkZ'];
			$pk->order = FullChunkDataPacket::ORDER_COLUMNS;
			if ($protocol >= Info::PROTOCOL_120) {
				$pk->data = $chunkData120;
				foreach ($subClientsId as $subClientId) {
					$pk->senderSubClientID = $subClientId;
					$pk->encode($protocol);
					if(!empty($pk->buffer)) {
						$str = Binary::writeVarInt(strlen($pk->buffer)) . $pk->buffer;
						$ordered = zlib_encode($str, ZLIB_ENCODING_DEFLATE, 7);
						$result[$protocol . ":{$subClientId}"] = $ordered;
					}
				}
			} else {
				$pk->data = $chunkData;
				$pk->encode($protocol);
				if(!empty($pk->buffer)) {
					$str = Binary::writeVarInt(strlen($pk->buffer)) . $pk->buffer;
					$ordered = zlib_encode($str, ZLIB_ENCODING_DEFLATE, 7);
					$result[$protocol . ":0"] = $ordered;
				}
			}
		}
		$this->externalQueue[] = serialize($result);
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
	
	private function sortData($data){
		$result = str_repeat("\x00", 4096);
		if($data !== $result){
			$i = 0;
			for($x = 0; $x < 16; ++$x){
				$zM = $x + 256;
				for($z = $x; $z < $zM; $z += 16){
					$yM = $z + 4096;
					for($y = $z; $y < $yM; $y += 256){
						$result{$i} = $data{$y};
						++$i;
					}
				}
			}
		}
		return $result;
	}
	
	private function sortHalfData($data) {
		$result = str_repeat("\x00", 2048);
		if ($data !== $result) {
			$i = 0;
			for ($x = 0; $x < 8; ++$x) {
				for ($z = 0; $z < 16; ++$z) {
					$zx = (($z << 3) | $x);
					for ($y = 0; $y < 8; ++$y) {
						$j = (($y << 8) | $zx);
						$j80 = ($j | 0x80);
						$i1 = ord($data{$j});
						$i2 = ord($data{$j80});
						$result{$i} = chr(($i2 << 4) | ($i1 & 0x0f));
						$result{$i | 0x80} = chr(($i1 >> 4) | ($i2 & 0xf0));
						$i++;
					}
				}
				$i += 128;
			}
		}
		return $result;
	}

}

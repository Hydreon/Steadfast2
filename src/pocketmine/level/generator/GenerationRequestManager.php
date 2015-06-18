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

namespace pocketmine\level\generator;

use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\utils\Binary;
use pocketmine\utils\ChunkException;

class GenerationRequestManager{

	/** @var Server */
	protected $server;
	/** @var GenerationThread */
	protected $generationThread;

	private $internalThreaded;
	private $externalThreaded;

	/**
	 * @param Server $server
	 */
	public function __construct(Server $server){
		$this->server = $server;
		$this->internalThreaded = new \Threaded();
		$this->externalThreaded = new \Threaded();
		$this->generationThread = new GenerationThread($this->internalThreaded, $this->externalThreaded, $server->getLogger(), $server->getLoader());
	}

	/**
	 * @param Level  $level
	 * @param string $generator
	 * @param array  $options
	 */
	public function openLevel(Level $level, $generator, array $options = []){
		$buffer = \chr(GenerationManager::PACKET_OPEN_LEVEL) . \pack("N", $level->getId()) . \pack("N", $level->getSeed()) .
			\pack("n", \strlen($generator)) . $generator . \serialize($options);

		$this->generationThread->pushMainToThreadPacket($buffer);
	}

	/**
	 * @param Level $level
	 */
	public function closeLevel(Level $level){
		$buffer = \chr(GenerationManager::PACKET_CLOSE_LEVEL) . \pack("N", $level->getId());
		$this->generationThread->pushMainToThreadPacket($buffer);
	}

	public function addNamespace($namespace, $path){
		$buffer = \chr(GenerationManager::PACKET_ADD_NAMESPACE) . \pack("n", \strlen($namespace)) . $namespace . $path;
		$this->generationThread->pushMainToThreadPacket($buffer);
	}

	protected function sendChunk($levelID, FullChunk $chunk){
		$buffer = \chr(GenerationManager::PACKET_SEND_CHUNK) . \pack("N", $levelID) . \chr(\strlen($class = \get_class($chunk))) . $class . $chunk->toBinary();
		$this->generationThread->pushMainToThreadPacket($buffer);
	}

	public function requestChunk(Level $level, $chunkX, $chunkZ){
		$buffer = \chr(GenerationManager::PACKET_REQUEST_CHUNK) . \pack("N", $level->getId()) . \pack("N", $chunkX) . \pack("N", $chunkZ);
		$this->generationThread->pushMainToThreadPacket($buffer);
	}

	protected function handleRequest($levelID, $chunkX, $chunkZ){
		if(($level = $this->server->getLevel($levelID)) instanceof Level){
			$chunk = $level->getChunk($chunkX, $chunkZ, \true);
			if($chunk instanceof FullChunk){
				$this->sendChunk($levelID, $chunk);
			}else{
				throw new ChunkException("Invalid Chunk given");
			}
		}else{
			$buffer = \chr(GenerationManager::PACKET_CLOSE_LEVEL) . \pack("N", $levelID);
			$this->generationThread->pushMainToThreadPacket($buffer);
		}
	}

	protected function receiveChunk($levelID, FullChunk $chunk){
		if(($level = $this->server->getLevel($levelID)) instanceof Level){
			$level->generateChunkCallback($chunk->getX(), $chunk->getZ(), $chunk);
		}else{
			$buffer = \chr(GenerationManager::PACKET_CLOSE_LEVEL) . \pack("N", $levelID);
			$this->generationThread->pushMainToThreadPacket($buffer);
		}
	}

	public function process(){
		$this->handlePackets();
	}

	public function handlePackets(){
		while(\strlen($packet = $this->generationThread->readThreadToMainPacket()) > 0){
			$pid = \ord($packet{0});
			$offset = 1;

			if($pid === GenerationManager::PACKET_REQUEST_CHUNK){
				$levelID = (\PHP_INT_SIZE === 8 ? \unpack("N", \substr($packet, $offset, 4))[1] << 32 >> 32 : \unpack("N", \substr($packet, $offset, 4))[1]);
				$offset += 4;
				$chunkX = (\PHP_INT_SIZE === 8 ? \unpack("N", \substr($packet, $offset, 4))[1] << 32 >> 32 : \unpack("N", \substr($packet, $offset, 4))[1]);
				$offset += 4;
				$chunkZ = (\PHP_INT_SIZE === 8 ? \unpack("N", \substr($packet, $offset, 4))[1] << 32 >> 32 : \unpack("N", \substr($packet, $offset, 4))[1]);
				$this->handleRequest($levelID, $chunkX, $chunkZ);
			}elseif($pid === GenerationManager::PACKET_SEND_CHUNK){
				$levelID = (\PHP_INT_SIZE === 8 ? \unpack("N", \substr($packet, $offset, 4))[1] << 32 >> 32 : \unpack("N", \substr($packet, $offset, 4))[1]);
				$offset += 4;
				$len = \ord($packet{$offset++});
				/** @var FullChunk $class */
				$class = \substr($packet, $offset, $len);
				$offset += $len;
				$level = $this->server->getLevel($levelID);
				if($level instanceof Level){
					$chunk = $class::fromBinary(\substr($packet, $offset), $level->getProvider());
					$this->receiveChunk($levelID, $chunk);
				}
			}
		}
	}

	public function shutdown(){
		$buffer = \chr(GenerationManager::PACKET_SHUTDOWN);
		$this->generationThread->pushMainToThreadPacket($buffer);
	}


}

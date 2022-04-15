<?php

namespace pocketmine;

use pocketmine\level\generator\biome\Biome;
use pocketmine\level\Level;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\utils\Binary;
use function chr;
use function count;
use function implode;

class ChunkStorage {

	protected $cache = [];
	/** @var ChunkMaker */
	protected $server;

	/**
	 * @param ChunkMaker $server
	 */
	public function __construct($server) {
		$this->server = $server;
		$this->tickProcessor();
	}

	public function tickProcessor() {
		while (!$this->server->isShutdown()) {
			$start = microtime(true);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.025) {
				@time_sleep_until(microtime(true) + 0.025 - $time);
			}
		}
	}

	protected function tick() {
		while (!is_null($pk = $this->server->readMainToThreadPacket())) {
			$data = unserialize($pk);
			switch ($data['event']) {
				case 'doChunk':
					$this->doChunk($data);
					break;
				case 'sendFromCache':
					$this->sendFromCache($data);
					break;
				case 'clearCache':
					$this->clearChunkCache($data);
					break;
			}
		}
	}

	protected function clearChunkCache($data) {
		$this->clearCache(Level::chunkHash($data['chunkX'], $data['chunkZ']));
	}

	protected function sendFromCache($data) {
		$buffer = $this->getCache(Level::chunkHash($data['chunkX'], $data['chunkZ']), ($data['protocol'] << 4) | $data['subClientId']);
		$this->server->sendData($data, $buffer);
	}

	protected function doChunk($data) {
		$protocol = $data['protocol'];
		$isAnvil = isset($data['isAnvil']) && $data['isAnvil'] == true;

		$subChunkCount = $isAnvil ? count($data['chunk']['sections']) : 8;

		$chunkData = "";

		if($protocol >= Info::PROTOCOL_475){
			//TODO: HACK! fill in fake subchunks to make up for the new negative space client-side
			for($y = 0; $y < 4; $y++){
				$subChunkCount++;
				$chunkData .= chr(8); //subchunk version 8
				$chunkData .= chr(0); //0 layers - client will treat this as all-air
			}
		}
		if ($isAnvil) {
			foreach ($data['chunk']['sections'] as $y => $sections) {
				if ($sections['empty'] == true) {
					$blockData = "\x00" . str_repeat("\x00", 6144);						
					$chunkData .= $blockData;
				} else {
					if (isset($data['isSorted']) && $data['isSorted'] == true) {
						$blockData = "\x00" . $sections['blocks'] . $sections['data'];
					} else {
						$blockData = "\x00" . $this->sortData($sections['blocks']) . $this->sortHalfData($sections['data']);
					}
					$chunkData .= $blockData;
				}
			}
			$biomes = $data["chunk"]["biomeColor"];
		} else {
			$blockIdArray = $data['blocks'];
			$blockDataArray = $data['data'];
			$countBlocksInChunk = 8;
			for ($blockIndex = 0; $blockIndex < $countBlocksInChunk; $blockIndex++) {
				$blockIdData = '';
				$blockDataData = '';
				for ($i = 0; $i < 256; $i++) {
					$startIndex = ($blockIndex + ($i << 3)) << 3;
					$blockIdData .= substr($blockIdArray, $startIndex << 1, 16);
					$blockDataData .= substr($blockDataArray, $startIndex, 8);
				}
				$blockData = "\x00" . $blockIdData . $blockDataData;
				$chunkData .= $blockData;
			}
			$biomes = $data["biomeColor"];
		}
		if($protocol >= Info::PROTOCOL_475){
			$count = $protocol >= Info::PROTOCOL_503 ? 24 : 25;
			for($i = 0; $i < $count; ++$i){
				$chunkData .= chr(0); //fake biome palette - 0 bpb, non-persistent
				$chunkData .= Binary::writeVarInt(Biome::PLAINS << 1); //fill plains for now
			}
		}else{
			$chunkData .= $biomes;
		}
		$chunkData .= Binary::writeByte(0) . implode('', $data['tiles']);
		$subClientId = $data['subClientId'];
		$pk = new FullChunkDataPacket();
		$pk->chunkX = $data['chunkX'];
		$pk->chunkZ = $data['chunkZ'];
		$pk->senderSubClientID = $subClientId;
		$pk->subChunkCount = $subChunkCount;
		$pk->data = $chunkData;
		$pk->encode($protocol);
		$buffer = $pk->getBuffer();
		$decodedBuffer = Binary::writeVarInt(strlen($buffer)) . $buffer;

		$buffer = zlib_encode($decodedBuffer, Player::getCompressAlg($protocol), 7);
        $this->server->sendData($data, $buffer);

		$this->setCache(Level::chunkHash($data['chunkX'], $data['chunkZ']), ($protocol << 4) | $subClientId, $buffer);
	}
	
	private function getSectionHash($data) {
		return substr(md5($data, true), 8);
	}

	private function sortData($data) {
		$result = str_repeat("\x00", 4096);
		if ($data !== $result) {
			$i = 0;
			for ($x = 0; $x < 16; ++$x) {
				$zM = $x + 256;
				for ($z = $x; $z < $zM; $z += 16) {
					$yM = $z + 4096;
					for ($y = $z; $y < $yM; $y += 256) {
						$result[$i] = $data[$y];
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
						$i1 = ord($data[$j]);
						$i2 = ord($data[$j80]);
						$result[$i] = chr(($i2 << 4) | ($i1 & 0x0f));
						$result[$i | 0x80] = chr(($i1 >> 4) | ($i2 & 0xf0));
						$i++;
					}
				}
				$i += 128;
			}
		}
		return $result;
	}

	protected function getCache($chunkIndex, $playerIndex) {
		return $this->cache[$chunkIndex][$playerIndex];
	}

	protected function setCache($chunkIndex, $playerIndex, $buffer) {
		$this->cache[$chunkIndex][$playerIndex] = $buffer;
	}

	protected function clearCache($chunkIndex) {
		unset($this->cache[$chunkIndex]);
	}

}

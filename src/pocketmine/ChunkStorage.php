<?php

namespace pocketmine;

use pocketmine\utils\Binary;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\level\Level;

class ChunkStorage {

	protected $cache = [];
	protected $server;

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
		if (isset($data['isAnvil']) && $data['isAnvil'] == true) {
			$chunkData = chr(count($data['chunk']['sections']));
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
			if ($protocol < Info::PROTOCOL_360) {
				$chunkData .= $data['chunk']['heightMap'];
			}
			$chunkData .= $data['chunk']['biomeColor'] . Binary::writeByte(0) . Binary::writeSignedVarInt(0) . implode('', $data['tiles']);
		} else {
			$blockIdArray = $data['blocks'];
			$blockDataArray = $data['data'];
			$countBlocksInChunk = 8;
			$chunkData = chr($countBlocksInChunk);
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
			$chunkData .= $data['heightMap'] . $data['biomeColor'] . Binary::writeLInt(0) . implode('', $data['tiles']);
		}
		$subClientId = $data['subClientId'];
		$pk = new FullChunkDataPacket();
		$pk->chunkX = $data['chunkX'];
		$pk->chunkZ = $data['chunkZ'];
		$pk->senderSubClientID = $subClientId;
		$pk->data = $chunkData;
		$pk->encode($protocol);
		$buffer = $pk->getBuffer();
		$decodedBuffer = Binary::writeVarInt(strlen($buffer)) . $buffer;
		$buffer = zlib_encode($decodedBuffer, ZLIB_ENCODING_DEFLATE, 7);
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

<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;

abstract class PEPacket extends DataPacket {
	
	const CLIENT_ID_MAIN_PLAYER = 0;
	const CLIENT_ID_SERVER = 0;
	
	public $senderSubClientID = self::CLIENT_ID_SERVER;
	
	public $targetSubClientID = self::CLIENT_ID_MAIN_PLAYER;

	abstract public function encode($playerProtocol);

	abstract public function decode($playerProtocol);
	
	protected function checkLength(int $len) {
		if ($this->offset + $len > strlen($this->buffer)) {
			throw new \Exception(get_class($this) . ": Try get {$len} bytes, offset = " . $this->offset . ", bufflen = " . strlen($this->buffer) . ", buffer = " . bin2hex(substr($string, 0, 250)));
		}
	}

	/**
	 * !IMPORTANT! Should be called at first line in decode
	 * @param integer $playerProtocol
	 */
	protected function getHeader($playerProtocol = 0) {
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->senderSubClientID = $this->getByte();
			$this->targetSubClientID = $this->getByte();
			if ($this->senderSubClientID > 4 || $this->targetSubClientID > 4) {
				throw new \Exception(get_class($this) . ": Packet decode headers error");
			}
		}
	}

	/**
	 * !IMPORTANT! Should be called at first line in encode
	 * @param integer $playerProtocol
	 */
	public function reset($playerProtocol = 0) {
		$this->buffer = chr(self::$packetsIds[$playerProtocol][$this::PACKET_NAME]);
		$this->offset = 0;
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte($this->senderSubClientID);
			$this->putByte($this->targetSubClientID);
			$this->offset = 2;
		}
	}
	
	public final static function convertProtocol($protocol) {
		switch ($protocol) {
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_270:
			case Info::PROTOCOL_271:
				return Info::PROTOCOL_260;
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_250:
				return Info::PROTOCOL_240;
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_222:
			case Info::PROTOCOL_223:
			case Info::PROTOCOL_224:
				return Info::PROTOCOL_221;
			case Info::PROTOCOL_220:
				return Info::PROTOCOL_220;
			case Info::PROTOCOL_200:
				return Info::PROTOCOL_200;
			case Info::PROTOCOL_134:
			case Info::PROTOCOL_135:
			case Info::PROTOCOL_136:
			case Info::PROTOCOL_137:
			case Info::PROTOCOL_140:
			case Info::PROTOCOL_141:
			case Info::PROTOCOL_150:
			case Info::PROTOCOL_160:
			case Info::PROTOCOL_201:
				return Info::PROTOCOL_120;
			case Info::PROTOCOL_110:
			case Info::PROTOCOL_111:
			case Info::PROTOCOL_112:
			case Info::PROTOCOL_113:
				return Info::PROTOCOL_110;
			case Info::PROTOCOL_105:
			case Info::PROTOCOL_106:
			case Info::PROTOCOL_107:
				return Info::PROTOCOL_105;
			default:
				return Info::BASE_PROTOCOL;
		}
	}
	
	private static $blockPallet = [];
	private static $blockPalletRevert = [];
	
	public static function initPallet() {
		$data = json_decode(file_get_contents(__DIR__ . "/../../BlockPallet.json"), true);
		$result = [];
		$revert = [];
		foreach ($data as $blockInfo) {
			$result[$blockInfo['id']][$blockInfo['data']] = $blockInfo['runtimeID'];
			$revert[$blockInfo['runtimeID']] = [$blockInfo['id'], $blockInfo['data']];
		}
		self::$blockPallet = $result;
		self::$blockPalletRevert = $revert;
	}
	
	public static function getBlockIDByRuntime($runtimeId, $playerProtocol) {
		if ($playerProtocol >= Info::PROTOCOL_240) {
			if ($runtimeId > 1978 + 99) {
				$runtimeId -= 99;
			} elseif ($runtimeId > 1760 + 77) {
				$runtimeId -= 77;
			} elseif ($runtimeId > 1642 + 47) {
				$runtimeId -= 47;
			} elseif ($runtimeId > 1467 + 45) {
				$runtimeId -= 45;
			} elseif ($runtimeId > 1370 + 32) {
				$runtimeId -= 32;
			} elseif ($runtimeId > 345 + 17) {
				$runtimeId -= 17;
			} elseif ($runtimeId > 275 + 15) {
				$runtimeId -= 15;
			}
		}
		if (isset(self::$blockPalletRevert[$runtimeId])) {
			return self::$blockPalletRevert[$runtimeId];
		}
		return [0, 0];
	}
	
	public static function getBlockRuntimeID($id, $meta, $playerProtocol) {
		$runtimeId = 0;
		if (isset(self::$blockPallet[$id][$meta])) {
			$runtimeId = self::$blockPallet[$id][$meta];
		} elseif (isset(self::$blockPallet[$id][0])) {
			$runtimeId = self::$blockPallet[$id][0];
		}
		if ($playerProtocol >= Info::PROTOCOL_240) {
			if ($runtimeId > 1978) {
				$runtimeId += 99;
			} elseif ($runtimeId > 1760) {
				$runtimeId += 77;
			} elseif ($runtimeId > 1642) {
				$runtimeId += 47;
			} elseif ($runtimeId > 1467) {
				$runtimeId += 45;
			} elseif ($runtimeId > 1370) {
				$runtimeId += 32;
			} elseif ($runtimeId > 345) {			
				$runtimeId += 17;
			} elseif ($runtimeId > 275) {
				$runtimeId += 15;
			}
		}
		return $runtimeId;
	}

}

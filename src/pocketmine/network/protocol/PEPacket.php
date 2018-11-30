<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\multiversion\BlockPallet;

abstract class PEPacket extends DataPacket {
	
	const CLIENT_ID_MAIN_PLAYER = 0;
	const CLIENT_ID_SERVER = 0;
	
	public $senderSubClientID = self::CLIENT_ID_SERVER;
	
	public $targetSubClientID = self::CLIENT_ID_MAIN_PLAYER;

	abstract public function encode($playerProtocol);

	abstract public function decode($playerProtocol);

	/**
	 * !IMPORTANT! Should be called at first line in decode
	 * @param integer $playerProtocol
	 */
	protected function getHeader($playerProtocol = 0) {
		if ($playerProtocol >= Info::PROTOCOL_280) {
			$header = $this->getSignedVarInt();
			$subclientIds = $header >> 10;
			$this->senderSubClientID = $subclientIds & 0x03;
			$this->targetSubClientID = ($subclientIds >> 2) & 0x03;
		} else if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->getByte(); // packetID
			$this->senderSubClientID = $this->getByte();
			$this->targetSubClientID = $this->getByte();
			if ($this->senderSubClientID > 4 || $this->targetSubClientID > 4) {
				throw new \Exception(get_class($this) . ": Packet decode headers error");
			}
		} else {
			$this->getByte(); // packetID
		}
	}

	/**
	 * !IMPORTANT! Should be called at first line in encode
	 * @param integer $playerProtocol
	 */
	public function reset($playerProtocol = 0) {
		if ($playerProtocol < Info::PROTOCOL_280) {
			parent::reset();
			$this->putByte(self::$packetsIds[$playerProtocol][$this::PACKET_NAME]);	
			if ($playerProtocol >= Info::PROTOCOL_120) {
				$this->putByte($this->senderSubClientID);
				$this->putByte($this->targetSubClientID);
			}
		} else {
			parent::reset();
			$packetID = self::$packetsIds[$playerProtocol][$this::PACKET_NAME];
			$header = ($this->targetSubClientID << 12) | ($this->senderSubClientID << 10) | $packetID;
			$this->putVarInt($header);
		}
	}
	
	public final static function convertProtocol($protocol) {
		switch ($protocol) {
			case Info::PROTOCOL_331:
				return Info::PROTOCOL_331;
			case Info::PROTOCOL_330:
				return Info::PROTOCOL_330;
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_312:
			case Info::PROTOCOL_313:
				return Info::PROTOCOL_311;
			case Info::PROTOCOL_310:
				return Info::PROTOCOL_310;
			case Info::PROTOCOL_290:
			case Info::PROTOCOL_291:
				return Info::PROTOCOL_290;
			case Info::PROTOCOL_282:
				return Info::PROTOCOL_282;
			case Info::PROTOCOL_281:
			case Info::PROTOCOL_280:
				return Info::PROTOCOL_280;
			case Info::PROTOCOL_274:
				return Info::PROTOCOL_274;
			case Info::PROTOCOL_273:
				return Info::PROTOCOL_273;
			case Info::PROTOCOL_271:
				return Info::PROTOCOL_271;
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_261:
			case Info::PROTOCOL_270:
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
			default:
				return Info::PROTOCOL_110;
		}
	}
	
	/** @var BlockPallet[] */
	private static $blockPalletes = [];
	
	public static function initPallet() {
		self::$blockPalletes = BlockPallet::initAll();
	}
	
	public static function getBlockIDByRuntime($runtimeId, $playerProtocol) {
		$pallet = self::getPallet($playerProtocol);
		return is_null($pallet) ? [ 0, 0, "" ] : $pallet->getBlockDataByRuntimeID($runtimeId);
	}
	
	public static function getBlockRuntimeID($id, $meta, $playerProtocol) {
		$pallet = self::getPallet($playerProtocol);
		return is_null($pallet) ? 0 : $pallet->getBlockRuntimeIDByData($id, $meta);
	}
	
	public static function getBlockPalletData($playerProtocol) {
		$pallet = self::getPallet($playerProtocol);
		return is_null($pallet) ? "" : $pallet->getDataForPackets();
	}
	
	/**
	 * 
	 * @param type $playerProtocol
	 * @return BlockPallet
	 */
	public static function getPallet($playerProtocol) {
		foreach (self::$blockPalletes as $protocol => $pallet) {
			if ($playerProtocol >= $protocol) {
				return $pallet;
			}
		}
		return null;
	}

}

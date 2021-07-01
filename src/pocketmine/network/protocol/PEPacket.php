<?php

namespace pocketmine\network\protocol;

use pocketmine\item\Item;
use pocketmine\network\multiversion\BlockPallet;
use function is_null;

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
		$header = $this->getVarInt();
		$subclientIds = $header >> 10;
		$this->senderSubClientID = $subclientIds & 0x03;
		$this->targetSubClientID = ($subclientIds >> 2) & 0x03;
	}

	/**
	 * !IMPORTANT! Should be called at first line in encode
	 * @param integer $playerProtocol
	 */
	public function reset($playerProtocol = 0) {
		parent::reset();
		$packetID = self::$packetsIds[$playerProtocol][$this::PACKET_NAME];
		$header = ($this->targetSubClientID << 12) | ($this->senderSubClientID << 10) | $packetID;
		$this->putVarInt($header);
	}
	
	public final static function convertProtocol($protocol) {
		switch ($protocol) {
			case Info::PROTOCOL_448:
				return Info::PROTOCOL_448;
			case Info::PROTOCOL_440:
				return Info::PROTOCOL_440;
			case Info::PROTOCOL_431:
				return Info::PROTOCOL_431;
			case Info::PROTOCOL_428:
				return Info::PROTOCOL_428;
			case Info::PROTOCOL_423:
			case Info::PROTOCOL_422:
				return Info::PROTOCOL_422;
			case Info::PROTOCOL_419:
				return Info::PROTOCOL_419;
			default:
				throw new \InvalidArgumentException("Unknown protocol $protocol");
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
		if ($playerProtocol >= Info::PROTOCOL_419) {
			$meta = self::getActualMeta($id, $meta);
		}
		return is_null($pallet) ? 0 : $pallet->getBlockRuntimeIDByData($id, $meta);
	}

	private static function getActualMeta($id, $meta) {
		if ($id == Item::ITEM_FRAME_BLOCK) {
			$array = [3 => 8, 4 => 5, 5 => 4];
			return $array[$meta]??$meta;
		}
		if ($id == Item::LEAVE2 && $meta > 7) {			
			return 7;
		}
		return $meta;
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

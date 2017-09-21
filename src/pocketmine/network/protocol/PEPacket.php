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
			throw new \Exception( get_class($this) .": Try get {$len} bytes, offset = " . $this->offset . ", bufflen = " . strlen($this->buffer));
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
			case Info::PROTOCOL_134:
			case Info::PROTOCOL_135:
			case Info::PROTOCOL_136:
			case Info::PROTOCOL_137:
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

}

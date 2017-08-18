<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;

abstract class PEPacket extends DataPacket {

	abstract public function encode($playerProtocol);

	abstract public function decode($playerProtocol);

	public function reset($playerProtocol = 0) {
		$this->buffer = chr(self::$packetsIds[$playerProtocol][$this::PACKET_NAME]);
		$this->offset = 0;
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->buffer .= "\x00\x00";
			$this->offset = 2;
		}
	}
	
	public final static function convertProtocol($protocol) {
		switch ($protocol) {
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_121:
			case Info::PROTOCOL_130:
			case Info::PROTOCOL_131:
			case Info::PROTOCOL_132:
			case Info::PROTOCOL_133:
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

<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\DataPacket;

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

}

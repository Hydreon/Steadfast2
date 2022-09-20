<?php

namespace pocketmine\network\protocol;

class RequestNetworkSettingsPacket extends PEPacket {
	const NETWORK_ID = Info::REQUEST_NETWORK_SETTINGS_PACKET;
	const PACKET_NAME = "REQUEST_NETWORK_SETTINGS_PACKET";

	/** @var int */
	public $protocolVersion;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->protocolVersion = $this->getInt();
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putInt($this->protocolVersion);
	}
}

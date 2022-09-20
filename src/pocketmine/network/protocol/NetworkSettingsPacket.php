<?php

namespace pocketmine\network\protocol;

class NetworkSettingsPacket extends PEPacket {
	const NETWORK_ID = Info::NETWORK_SETTINGS_PACKET;
	const PACKET_NAME = "NETWORK_SETTINGS_PACKET";

	const COMPRESSION_ZLIB = 0;
	const COMPRESSION_SNAPPY = 1;

	const COMPRESS_NOTHING = 0;
	const COMPRESS_EVERYTHING = 1;

	/** @var int */
	public $compressionThreshold = self::COMPRESS_EVERYTHING;
	/** @var int */
	public $compressionAlgorithm = self::COMPRESSION_ZLIB;
	/** @var bool */
	public $enableClientThrottling = false;
	/** @var int */
	public $clientThrottleThreshold = 0;
	/** @var float */
	public $clientThrottleScalar = 0;

	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->compressionThreshold = $this->getLShort();
		$this->compressionAlgorithm = $this->getLShort();
		if($playerProtocol >= Info::PROTOCOL_554) {
			$this->enableClientThrottling = $this->getByte() !== 0;
			$this->clientThrottleThreshold = $this->getByte();
			$this->clientThrottleScalar = $this->getLFloat();
		}
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putLShort($this->compressionThreshold);
		$this->putLShort($this->compressionAlgorithm);
		if($playerProtocol >= Info::PROTOCOL_554) {
			$this->putBool($this->enableClientThrottling);
			$this->putByte($this->clientThrottleThreshold);
			$this->putLFloat($this->clientThrottleScalar);
		}
	}
}

<?php

namespace pocketmine\network\proxy;

use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;

abstract class ProxyPacket extends BinaryStream {

	const NETWORK_ID = 0;

	public $isEncoded = false;
	private $channel = 0;

	public function pid() {
		return $this::NETWORK_ID;
	}

	abstract public function encode();

	abstract public function decode();

	public function reset() {
		$this->buffer = chr($this::NETWORK_ID);
		$this->offset = 0;
	}

	/**
	 * @deprecated This adds extra overhead on the network, so its usage is now discouraged. It was a test for the viability of this.
	 */
	public function setChannel($channel) {
		$this->channel = (int) $channel;
		return $this;
	}

	public function getChannel() {
		return $this->channel;
	}

	public function clean() {
		$this->buffer = null;
		$this->isEncoded = false;
		$this->offset = 0;
		return $this;
	}

	public function __debugInfo() {
		$data = [];
		foreach ($this as $k => $v) {
			if ($k === "buffer") {
				$data[$k] = bin2hex($v);
			} elseif (is_string($v) or ( is_object($v) and method_exists($v, "__toString"))) {
				$data[$k] = Utils::printable((string) $v);
			} else {
				$data[$k] = $v;
			}
		}

		return $data;
	}

}

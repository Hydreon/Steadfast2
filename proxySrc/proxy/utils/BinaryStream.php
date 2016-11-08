<?php

namespace proxy\utils;

class BinaryStream extends \stdClass {

	public $offset;
	public $buffer;

	public function __construct($buffer = "", $offset = 0) {
		$this->buffer = $buffer;
		$this->offset = $offset;
	}

	public function reset() {
		$this->buffer = "";
		$this->offset = 0;
	}

	public function setBuffer($buffer = null, $offset = 0) {
		$this->buffer = $buffer;
		$this->offset = (int) $offset;
	}

	public function getOffset() {
		return $this->offset;
	}

	public function getBuffer() {
		return $this->buffer;
	}

	public function get($len) {
		if ($len < 0) {
			$this->offset = strlen($this->buffer) - 1;
			return "";
		} elseif ($len === true) {
			return substr($this->buffer, $this->offset);
		}

		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function put($str) {
		$this->buffer .= $str;
	}

	public function getLong() {
		return Binary::readLong($this->get(8));
	}

	public function putLong($v) {
		$this->buffer .= Binary::writeLong($v);
	}

	public function getInt() {
		return Binary::readInt($this->get(4));
	}

	public function putInt($v) {
		$this->buffer .= Binary::writeInt($v);
	}

	public function getLLong() {
		return Binary::readLLong($this->get(8));
	}

	public function putLLong($v) {
		$this->buffer .= Binary::writeLLong($v);
	}

	public function getLInt() {
		return Binary::readLInt($this->get(4));
	}

	public function putLInt($v) {
		$this->buffer .= Binary::writeLInt($v);
	}

	public function getShort($signed = true) {
		return $signed ? Binary::readSignedShort($this->get(2)) : Binary::readShort($this->get(2));
	}

	public function putShort($v) {
		$this->buffer .= Binary::writeShort($v);
	}

	public function getFloat() {
		return Binary::readFloat($this->get(4));
	}

	public function putFloat($v) {
		$this->buffer .= Binary::writeFloat($v);
	}

	public function getLShort($signed = true) {
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort($v) {
		$this->buffer .= Binary::writeLShort($v);
	}

	public function getLFloat() {
		return Binary::readLFloat($this->get(4));
	}

	public function putLFloat($v) {
		$this->buffer .= Binary::writeLFloat($v);
	}

	public function getTriad() {
		return Binary::readTriad($this->get(3));
	}

	public function putTriad($v) {
		$this->buffer .= Binary::writeTriad($v);
	}

	public function getLTriad() {
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad($v) {
		$this->buffer .= Binary::writeLTriad($v);
	}

	public function getByte() {
		return ord($this->buffer{$this->offset++});
	}

	public function putByte($v) {
		$this->buffer .= chr($v);
	}

	public function getDataArray($len = 10) {
		$data = [];
		for ($i = 1; $i <= $len and ! $this->feof(); ++$i) {
			$data[] = $this->get($this->getTriad());
		}

		return $data;
	}

	public function putDataArray(array $data = []) {
		foreach ($data as $v) {
			$this->putTriad(strlen($v));
			$this->put($v);
		}
	}

	public function getUUID() {
		return UUID::fromBinary($this->get(16));
	}

	public function putUUID(UUID $uuid) {
		$this->put($uuid->toBinary());
	}

	public function getString() {
		return $this->get($this->getShort());
	}

	public function putString($v) {
		$this->putShort(strlen($v));
		$this->put($v);
	}

	public function feof() {
		return !isset($this->buffer{$this->offset});
	}

}

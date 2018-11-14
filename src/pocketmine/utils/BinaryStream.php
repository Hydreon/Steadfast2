<?php

namespace pocketmine\utils;

use pocketmine\item\Item;
use pocketmine\nbt\NBT;

class BinaryStream extends \MCBinaryStream {

	private function writeErrorLog($depth = 3) {
		$depth = max($depth, 3);
		$backtrace = debug_backtrace(2, $depth);
		$result = __CLASS__ . "::" . __METHOD__ . " -> " . PHP_EOL;
		foreach ($backtrace as $k => $v) {
			$result .= "\t[line " . (isset($backtrace[$k]['line']) ? $backtrace[$k]['line'] : 'unknown line') . "] " . (isset($backtrace[$k]['class']) ? $backtrace[$k]['class'] : 'unknown class') . " -> " . (isset($backtrace[$k]['function']) ? $backtrace[$k]['function'] : 'unknown function') . PHP_EOL;
		}
		error_log($result);
	}

	public function __get($name) {
		$this->writeErrorLog();
		switch ($name) {
			case "buffer":
				return $this->getBuffer();
			case "offset":
				return $this->getOffset();
		}
	}

	public function __set($name, $value) {
		$this->writeErrorLog();
		switch ($name) {
			case "buffer":
				$this->setBuffer($value);
				return;
			case "offset":
				$this->setOffset($value);
				return;
		}
	}

	public function getUUID() {
		$part1 = $this->getLInt();
		$part0 = $this->getLInt();
		$part3 = $this->getLInt();
		$part2 = $this->getLInt();
		return new UUID($part0, $part1, $part2, $part3);
	}

	public function putUUID(UUID $uuid) {
		$this->putLInt($uuid->getPart(1));
		$this->putLInt($uuid->getPart(0));
		$this->putLInt($uuid->getPart(3));
		$this->putLInt($uuid->getPart(2));
	}

	public function getSlot($playerProtocol) {		
		$id = $this->getSignedVarInt();		
		if ($id <= 0) {
			return Item::get(Item::AIR, 0, 0);
		}
		
		$aux = $this->getSignedVarInt();
		$meta = $aux >> 8;
		$count = $aux & 0xff;
		
		$nbtLen = $this->getLShort();		
		$nbt = "";		
		if ($nbtLen > 0) {
			$nbt = $this->get($nbtLen);
		} elseif($nbtLen == -1) {
			$nbtCount = $this->getVarInt();
			for ($i = 0; $i < $nbtCount; $i++) {
				$nbtTag = new NBT(NBT::LITTLE_ENDIAN);
				$nbtTag->read(substr($this->buffer, $this->offset), false, true);
				$nbt = $nbtTag->getData();
				$this->offset += $nbtTag->getOffset();
			}
		}
		// $this->offset += 2;
		$this->get(2);
		
		return Item::get($id, $meta, $count, $nbt);
	}

	public function putSlot(Item $item, $playerProtocol) {
		if ($item->getId() === 0) {
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putSignedVarInt(($item->getDamage() === null ? 0  : ($item->getDamage() << 8)) + $item->getCount());	
		$nbt = $item->getCompound();	
		$this->putLShort(strlen($nbt));
		$this->put($nbt);
		$this->putByte(0);
		$this->putByte(0);
	}
	
}

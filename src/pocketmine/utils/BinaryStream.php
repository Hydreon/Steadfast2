<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\utils;

#include <rules/DataPacket.h>

#ifndef COMPILE

#endif

use pocketmine\item\Item;


class BinaryStream extends \stdClass{

	public $offset;
	public $buffer;
	
	public function __construct($buffer = "", $offset = 0){
		$this->buffer = $buffer;
		$this->offset = $offset;
	}

	public function reset(){
		$this->buffer = "";
		$this->offset = 0;
	}

	public function setBuffer($buffer = null, $offset = 0){
		$this->buffer = $buffer;
		$this->offset = (int) $offset;
	}

	public function getOffset(){
		return $this->offset;
	}

	public function getBuffer(){
		return $this->buffer;
	}

	public function get($len){
		if($len < 0){
			$this->offset = strlen($this->buffer) - 1;
			return "";
		}elseif($len === true){
			return substr($this->buffer, $this->offset);
		}

		return $len === 1 ? $this->buffer{$this->offset++} : substr($this->buffer, ($this->offset += $len) - $len, $len);
	}

	public function put($str){
		$this->buffer .= $str;
	}

	public function getLong(){
		return Binary::readLong($this->get(8));
	}

	public function putLong($v){
		$this->buffer .= Binary::writeLong($v);
	}

	public function getInt(){
		return Binary::readInt($this->get(4));
	}

	public function putInt($v){
		$this->buffer .= Binary::writeInt($v);
	}

	public function getLLong(){
		return Binary::readLLong($this->get(8));
	}

	public function putLLong($v){
		$this->buffer .= Binary::writeLLong($v);
	}

	public function getLInt(){
		return Binary::readLInt($this->get(4));
	}

	public function putLInt($v){
		$this->buffer .= Binary::writeLInt($v);
	}

	public function getShort($signed = true){
		return $signed ? Binary::readSignedShort($this->get(2)) : Binary::readShort($this->get(2));
	}

	public function putShort($v){
		$this->buffer .= Binary::writeShort($v);
	}

	public function getFloat(){
		return Binary::readFloat($this->get(4));
	}

	public function putFloat($v){
		$this->buffer .= Binary::writeFloat($v);
	}

	public function getLShort($signed = true){
		return $signed ? Binary::readSignedLShort($this->get(2)) : Binary::readLShort($this->get(2));
	}

	public function putLShort($v){
		$this->buffer .= Binary::writeLShort($v);
	}

	public function getLFloat(){
		return Binary::readLFloat($this->get(4));
	}

	public function putLFloat($v){
		$this->buffer .= Binary::writeLFloat($v);
	}


	public function getTriad(){
		return Binary::readTriad($this->get(3));
	}

	public function putTriad($v){
		$this->buffer .= Binary::writeTriad($v);
	}


	public function getLTriad(){
		return Binary::readLTriad($this->get(3));
	}

	public function putLTriad($v){
		$this->buffer .= Binary::writeLTriad($v);
	}

	public function getByte(){
		return ord($this->buffer{$this->offset++});
	}

	public function putByte($v){
		$this->buffer .= chr($v);
	}

	public function getDataArray($len = 10){
		$data = [];
		for($i = 1; $i <= $len and !$this->feof(); ++$i){
			$data[] = $this->get($this->getTriad());
		}

		return $data;
	}

	public function putDataArray(array $data = []){
		foreach($data as $v){
			$this->putTriad(strlen($v));
			$this->put($v);
		}
	}

	public function getUUID(){
		return UUID::fromBinary($this->get(16));
	}

	public function putUUID(UUID $uuid){
		$this->put($uuid->toBinary());
	}

	public function getSlot(){		
		$id = $this->getSignedVarInt();		
		if($id <= 0){
			return Item::get(0, 0, 0);
		}
	
		$aux = $this->getSignedVarInt();
		$meta = $aux >> 8;
		$count = $aux & 0xff;
				
		$nbtLen = $this->getLShort();		
		$nbt = "";		
		if($nbtLen > 0){
			$nbt = $this->get($nbtLen);
		}
		
		return Item::get(
			$id,
			$meta,
			$count,
			$nbt
		);
	}

	public function putSlot(Item $item){
		if($item->getId() === 0){
			$this->putSignedVarInt(0);
			return;
		}
		$this->putSignedVarInt($item->getId());
		$this->putSignedVarInt(($item->getDamage() === null ? 0  : ($item->getDamage() << 8)) + $item->getCount());	
		$nbt = $item->getCompound();		
		$this->putLShort(strlen($nbt));
		$this->put($nbt);		
	}

	public function feof(){
		return !isset($this->buffer{$this->offset});
	}
	
	
	public function getSignedVarInt() {
		$result = $this->getVarInt();
		if ($result % 2 == 0) {
			$result = $result / 2;
		} else {
			$result = (-1) * ($result + 1) / 2;
		}
		return $result;
	}

	public function getVarInt() {
		$result = $shift = 0;
		do {
			$byte = $this->getByte();
			$result |= ($byte & 0x7f) << $shift;
			$shift += 7;
		} while ($byte > 0x7f);
		return $result;
	}

	public function putSignedVarInt($v) {
		$this->put(Binary::writeSignedVarInt($v));
	}

	public function putVarInt($v) {
		$this->put(Binary::writeVarInt($v));
	}

	public function getString(){
		return $this->get($this->getVarInt());
	}
	public function putString($v){
		$this->putVarInt(strlen($v));
		$this->put($v);
	}
	
}

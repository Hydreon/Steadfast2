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

/**
 * Named Binary Tag handling classes
 */
namespace pocketmine\nbt;

use pocketmine\item\Item;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArray;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\End;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\IntArray;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\NamedTAG;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\utils\Utils;

#ifndef COMPILE
use pocketmine\utils\Binary;

#endif


#include <rules/NBT.h>

/**
 * Named Binary Tag encoder/decoder
 */
class NBT{

	const LITTLE_ENDIAN = 0;
	const BIG_ENDIAN = 1;
	const TAG_End = 0;
	const TAG_Byte = 1;
	const TAG_Short = 2;
	const TAG_Int = 3;
	const TAG_Long = 4;
	const TAG_Float = 5;
	const TAG_Double = 6;
	const TAG_ByteArray = 7;
	const TAG_String = 8;
	const TAG_Enum = 9;
	const TAG_Compound = 10;
	const TAG_IntArray = 11;

	public $buffer;
	private $offset;
	public $endianness;
	private $data;


	/**
	 * @param Item $item
	 * @param int  $slot
	 * @return Compound
	 */
	public static function putItemHelper(Item $item, $slot = null){
		$tag = new Compound('Item', [
			"id" => new ShortTag("id", $item->getId()),
			"Count" => new ByteTag("Count", $item->getCount()),
			"Damage" => new ShortTag("Damage", $item->getDamage())
		]);

		if($slot !== null){
			$tag->Slot = new ByteTag("Slot", (int) $slot);
		}

		if($item->hasCompound()){
			$tag->tag = clone $item->getNamedTag();
//			$tag->tag->setName("tag");
		}

		return $tag;
	}

	/**
	 * @param Compound $tag
	 * @return Item
	 */
	public static function getItemHelper(Compound $tag){
		if(!isset($tag->id) or !isset($tag->Count)){
			return Item::get(0);
		}

		$item = Item::get($tag->id->getValue(), !isset($tag->Damage) ? 0 : $tag->Damage->getValue(), $tag->Count->getValue());
		
		if(isset($tag->tag) and $tag->tag instanceof Compound){
			$item->setNamedTag($tag->tag);
		}

		return $item;
	}

	public static function matchList(Enum $tag1, Enum $tag2){
		if($tag1->getName() !== $tag2->getName() or $tag1->getCount() !== $tag2->getCount()){
			return false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return false;
			}

			if($v instanceof Compound){
				if(!self::matchTree($v, $tag2->{$k})){
					return false;
				}
			}elseif($v instanceof Enum){
				if(!self::matchList($v, $tag2->{$k})){
					return false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return false;
				}
			}
		}

		return true;
	}

	public static function matchTree(Compound $tag1, Compound $tag2){
		if($tag1->getCount() !== $tag2->getCount()){
			return false;
		}

		foreach($tag1 as $k => $v){
			if(!($v instanceof Tag)){
				continue;
			}

			if(!isset($tag2->{$k}) or !($tag2->{$k} instanceof $v)){
				return false;
			}

			if($v instanceof Compound){
				if(!self::matchTree($v, $tag2->{$k})){
					return false;
				}
			}elseif($v instanceof Enum){
				if(!self::matchList($v, $tag2->{$k})){
					return false;
				}
			}else{
				if($v->getValue() !== $tag2->{$k}->getValue()){
					return false;
				}
			}
		}

		return true;
	}

	public static function parseJSON($data, &$offset = 0){
		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data{$offset};
			if($c === "{"){
				++$offset;
				$data = self::parseCompound($data, $offset);
				return new Compound("", $data);
			}elseif($c !== " " and $c !== "\r" and $c !== "\n" and $c !== "\t"){
				throw new \Exception("Syntax error: unexpected '$c' at offset $offset");
			}
		}

		return null;
	}

	private static function parseList($str, &$offset = 0){
		$len = strlen($str);


		$key = 0;
		$value = null;

		$data = [];

		for(; $offset < $len; ++$offset){
			if($str{$offset - 1} === "]"){
				break;
			}elseif($str{$offset} === "]"){
				++$offset;
				break;
			}

			$value = self::readValue($str, $offset, $type);

			switch($type){
				case NBT::TAG_Byte:
					$data[$key] = new ByteTag($key, $value);
					break;
				case NBT::TAG_Short:
					$data[$key] = new ShortTag($key, $value);
					break;
				case NBT::TAG_Int:
					$data[$key] = new IntTag($key, $value);
					break;
				case NBT::TAG_Long:
					$data[$key] = new LongTag($key, $value);
					break;
				case NBT::TAG_Float:
					$data[$key] = new FloatTag($key, $value);
					break;
				case NBT::TAG_Double:
					$data[$key] = new DoubleTag($key, $value);
					break;
				case NBT::TAG_ByteArray:
					$data[$key] = new ByteArray($key, $value);
					break;
				case NBT::TAG_String:
					$data[$key] = new ByteTag($key, $value);
					break;
				case NBT::TAG_Enum:
					$data[$key] = new Enum($key, $value);
					break;
				case NBT::TAG_Compound:
					$data[$key] = new Compound($key, $value);
					break;
				case NBT::TAG_IntArray:
					$data[$key] = new IntArray($key, $value);
					break;
			}

			$key++;
		}

		return $data;
	}

	private static function parseCompound($str, &$offset = 0){
		$len = strlen($str);

		$data = [];

		for(; $offset < $len; ++$offset){
			if($str{$offset - 1} === "}"){
				break;
			}elseif($str{$offset} === "}"){
				++$offset;
				break;
			}

			$key = self::readKey($str, $offset);
			$value = self::readValue($str, $offset, $type);

			switch($type){
				case NBT::TAG_Byte:
					$data[$key] = new ByteTag($key, $value);
					break;
				case NBT::TAG_Short:
					$data[$key] = new ShortTag($key, $value);
					break;
				case NBT::TAG_Int:
					$data[$key] = new IntTag($key, $value);
					break;
				case NBT::TAG_Long:
					$data[$key] = new LongTag($key, $value);
					break;
				case NBT::TAG_Float:
					$data[$key] = new FloatTag($key, $value);
					break;
				case NBT::TAG_Double:
					$data[$key] = new DoubleTag($key, $value);
					break;
				case NBT::TAG_ByteArray:
					$data[$key] = new ByteArray($key, $value);
					break;
				case NBT::TAG_String:
					$data[$key] = new StringTag($key, $value);
					break;
				case NBT::TAG_Enum:
					$data[$key] = new Enum($key, $value);
					break;
				case NBT::TAG_Compound:
					$data[$key] = new Compound($key, $value);
					break;
				case NBT::TAG_IntArray:
					$data[$key] = new IntArray($key, $value);
					break;
			}
		}

		return $data;
	}

	private static function readValue($data, &$offset, &$type = null){
		$value = "";
		$type = null;
		$inQuotes = false;

		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data{$offset};

			if(!$inQuotes and ($c === " " or $c === "\r" or $c === "\n" or $c === "\t" or $c === "," or $c === "}" or $c === "]")){
				if($c === "," or $c === "}" or $c === "]"){
					break;
				}
			}elseif($c === '"'){
				$inQuotes = !$inQuotes;
				if($type === null){
					$type = self::TAG_String;
				}elseif($inQuotes){
					throw new \Exception("Syntax error: invalid quote at offset $offset");
				}
			}elseif($c === "\\"){
				$value .= isset($data{$offset + 1}) ? $data{$offset + 1} : "";
				++$offset;
			}elseif($c === "{" and !$inQuotes){
				if($value !== ""){
					throw new \Exception("Syntax error: invalid compound start at offset $offset");
				}
				++$offset;
				$value = self::parseCompound($data, $offset);
				$type = self::TAG_Compound;
				break;
			}elseif($c === "[" and !$inQuotes){
				if($value !== ""){
					throw new \Exception("Syntax error: invalid list start at offset $offset");
				}
				++$offset;
				$value = self::parseList($data, $offset);
				$type = self::TAG_Enum;
				break;
			}else{
				$value .= $c;
			}
		}

		if($value === ""){
			throw new \Exception("Syntax error: invalid empty value at offset $offset");
		}

		if($type === null and strlen($value) > 0){
			$value = trim($value);
			$last = strtolower(substr($value, -1));
			$part = substr($value, 0, -1);

			if($last !== "b" and $last !== "s" and $last !== "l" and $last !== "f" and $last !== "d"){
				$part = $value;
				$last = null;
			}

			if($last !== "f" and $last !== "d" and ((string) ((int) $part)) === $part){
				if($last === "b"){
					$type = self::TAG_Byte;
				}elseif($last === "s"){
					$type = self::TAG_Short;
				}elseif($last === "l"){
					$type = self::TAG_Long;
				}else{
					$type = self::TAG_Int;
				}
				$value = (int) $part;
			}elseif(is_numeric($part)){
				if($last === "f" or $last === "d" or strpos($part, ".") !== false){
					if($last === "f"){
						$type = self::TAG_Float;
					}elseif($last === "d"){
						$type = self::TAG_Double;
					}else{
						$type = self::TAG_Float;
					}
					$value = (float) $part;
				}else{
					if($last === "l"){
						$type = self::TAG_Long;
					}else{
						$type = self::TAG_Int;
					}

					$value = $part;
				}
			}else{
				$type = self::TAG_String;
			}
		}

		return $value;
	}

	private static function readKey($data, &$offset){
		$key = "";

		$len = strlen($data);
		for(; $offset < $len; ++$offset){
			$c = $data{$offset};

			if($c === ":"){
				++$offset;
				break;
			}elseif($c !== " " and $c !== "\r" and $c !== "\n" and $c !== "\t"){
				$key .= $c;
			}
		}

		if($key === ""){
			throw new \Exception("Syntax error: invalid empty key at offset $offset");
		}

		return $key;
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

	public function put($v){
		$this->buffer .= $v;
	}

	public function feof(){
		return !isset($this->buffer{$this->offset});
	}

	public function __construct($endianness = self::LITTLE_ENDIAN){
		$this->offset = 0;
		$this->endianness = $endianness & 0x01;
	}

	public function read($buffer, $doMultiple = false, $new = false){
		$this->offset = 0;
		$this->buffer = $buffer;
		$this->data = $this->readTag($new);
		if($doMultiple and $this->offset < strlen($this->buffer)){
			$this->data = [$this->data];
			do{
				$this->data[] = $this->readTag($new);				
			}while($this->offset < strlen($this->buffer));
		}
		$this->buffer = "";
	}

	public function readCompressed($buffer, $compression = ZLIB_ENCODING_GZIP){
		$this->read(zlib_decode($buffer));
	}

	/**
	 * @return string|bool
	 */
	public function write($old = false){
		$this->offset = 0;
		$this->buffer = "";

		if($this->data instanceof Compound){
			$this->writeTag($this->data, $old);

			return $this->buffer;
		}elseif(is_array($this->data)){
			foreach($this->data as $tag){
				$this->writeTag($tag, $old);
			}
			return $this->buffer;
		}

		return false;
	}

	public function writeCompressed($compression = ZLIB_ENCODING_GZIP, $level = 7){
		if(($write = $this->write(true)) !== false){
			return zlib_encode($write, $compression, $level);
		}

		return false;
	}
	
	private function checkGetString($new = false) {
		if ($new) {
			$data = $this->getNewString();
		} else {
			$data = $this->getString();
		}
		return $data;
	}

	public function readTag($new = false){
		$tagType = $this->getByte();
		switch($tagType){
			case NBT::TAG_Byte:
				$tag = new ByteTag($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_Short:
				$tag = new ShortTag($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_Int:
				$tag = new IntTag($this->checkGetString($new));
				$tag->read($this, $new);
				break;
			case NBT::TAG_Long:
				$tag = new LongTag($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_Float:
				$tag = new FloatTag($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_Double:
				$tag = new DoubleTag($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_ByteArray:
				$tag = new ByteArray($this->checkGetString($new));
				$tag->read($this);
				break;
			case NBT::TAG_String:
				$tag = new StringTag($this->checkGetString($new));
				$tag->read($this, $new);
				break;
			case NBT::TAG_Enum:
				$tag = new Enum($this->checkGetString($new));
				$tag->read($this, $new);
				break;
			case NBT::TAG_Compound:
				$tag = new Compound($this->checkGetString($new));
				$tag->read($this, $new);
				break;
			case NBT::TAG_IntArray:
				$tag = new IntArray($this->checkGetString($new));
				$tag->read($this);
				break;

			case NBT::TAG_End: //No named tag
			default:
				$tag = new End;
				break;
		}
		return $tag;
	}

	public function writeTag(Tag $tag, $old = false){
		$this->buffer .= chr($tag->getType());
		if($tag instanceof NamedTAG){
			if ($old) {
				$this->putOldString($tag->getName());
			} else {
				$this->putString($tag->getName());
			}
		}
		$tag->write($this, $old);
	}

	public function getByte(){
		return ord($this->get(1));
	}

	public function putByte($v){
		$this->buffer .= chr($v);
	}

	public function getShort(){
		return $this->endianness === self::BIG_ENDIAN ? unpack("n", $this->get(2))[1] : unpack("v", $this->get(2))[1];
	}

	public function putShort($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? pack("n", $v) : pack("v", $v);
	}

	public function getInt(){
		return $this->endianness === self::BIG_ENDIAN ? Binary::readInt($this->get(4)) : Binary::readLInt($this->get(4));
	}
	
	public function getNewInt(){
		return $this->getSignedVarInt();
		
	}

	public function putOldInt($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? pack("N", $v) : pack("V", $v);
	}
	
	public function putInt($v){
		$this->putSignedVarInt($v);
	}

	public function getLong(){
		return $this->endianness === self::BIG_ENDIAN ? Binary::readLong($this->get(8)) : Binary::readLLong($this->get(8));
	}

	public function putLong($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? Binary::writeLong($v) : Binary::writeLLong($v);
	}

	public function getFloat(){
		return $this->endianness === self::BIG_ENDIAN ? (ENDIANNESS === 0 ? unpack("f", $this->get(4))[1] : unpack("f", strrev($this->get(4)))[1]) : (ENDIANNESS === 0 ? unpack("f", strrev($this->get(4)))[1] : unpack("f", $this->get(4))[1]);
	}

	public function putFloat($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (ENDIANNESS === 0 ? pack("f", $v) : strrev(pack("f", $v))) : (ENDIANNESS === 0 ? strrev(pack("f", $v)) : pack("f", $v));
	}

	public function getDouble(){
		return $this->endianness === self::BIG_ENDIAN ? (ENDIANNESS === 0 ? unpack("d", $this->get(8))[1] : unpack("d", strrev($this->get(8)))[1]) : (ENDIANNESS === 0 ? unpack("d", strrev($this->get(8)))[1] : unpack("d", $this->get(8))[1]);
	}

	public function putDouble($v){
		$this->buffer .= $this->endianness === self::BIG_ENDIAN ? (ENDIANNESS === 0 ? pack("d", $v) : strrev(pack("d", $v))) : (ENDIANNESS === 0 ? strrev(pack("d", $v)) : pack("d", $v));
	}

	public function getString(){
		return $this->get($this->endianness === 1 ? unpack("n", $this->get(2))[1] : unpack("v", $this->get(2))[1]);
	}
	
	public function getNewString(){
		$len = $this->getVarInt();
		return $this->get($len);
	}

	public function putOldString($v){
		$this->buffer .= $this->endianness === 1 ? pack("n", strlen($v)) : pack("v", strlen($v));
		$this->buffer .= $v;
	}
	
	public function putString($v){
		$this->putVarInt(strlen($v));
		$this->buffer .= $v;
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
	
	public function getSignedVarInt() {
		$result = $this->getVarInt();
		if ($result % 2 == 0) {
			$result = $result / 2;
		} else {
			$result = (-1) * ($result + 1) / 2;
		}
		return $result;
	}
	
	public function putSignedVarInt($v) {
		$this->buffer .= Binary::writeSignedVarInt($v);
	}

	public function putVarInt($v) {
		$this->buffer .= Binary::writeVarInt($v);
	}

	public function getArray(){
		$data = [];
		self::toArray($data, $this->data);
	}

	private static function toArray(array &$data, Tag $tag){
		/** @var Compound[]|Enum[]|IntArray[] $tag */
		foreach($tag as $key => $value){
			if($value instanceof Compound or $value instanceof Enum or $value instanceof IntArray){
				$data[$key] = [];
				self::toArray($data[$key], $value);
			}else{
				$data[$key] = $value->getValue();
			}
		}
	}

	public static function fromArrayGuesser($key, $value){
		if(is_int($value)){
			return new IntTag($key, $value);
		}elseif(is_float($value)){
			return new FloatTag($key, $value);
		}elseif(is_string($value)){
			return new StringTag($key, $value);
		}elseif(is_bool($value)){
			return new ByteTag($key, $value ? 1 : 0);
		}

		return null;
	}

	private static function fromArray(Tag $tag, array $data, callable $guesser){
		foreach($data as $key => $value){
			if(is_array($value)){
				$isNumeric = true;
				$isIntArray = true;
				foreach($value as $k => $v){
					if(!is_numeric($k)){
						$isNumeric = false;
						break;
					}elseif(!is_int($v)){
						$isIntArray = false;
					}
				}
				$tag{$key} = $isNumeric ? ($isIntArray ? new IntArray($key, []) : new Enum($key, [])) : new Compound($key, []);
				self::fromArray($tag->{$key}, $value, $guesser);
			}else{
				$v = call_user_func($guesser, $key, $value);
				if($v instanceof Tag){
					$tag{$key} = $v;
				}
			}
		}
	}

	public function setArray(array $data, callable $guesser = null){
		$this->data = new Compound("", []);
		self::fromArray($this->data, $data, $guesser === null ? [self::class, "fromArrayGuesser"] : $guesser);
	}

	/**
	 * @return Compound|array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 * @param Compound|array $data
	 */
	public function setData($data){
		$this->data = $data;
	}

}
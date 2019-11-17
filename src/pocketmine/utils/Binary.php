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
 * Various Utilities used around the code
 */
namespace pocketmine\utils;

use InvalidArgumentException;
use function chr;
use function define;
use function defined;
use function ord;
use function pack;
use function preg_replace;
use function round;
use function sprintf;
use function substr;
use function unpack;
use const PHP_INT_MAX;
use pocketmine\entity\Entity;
use pocketmine\utils\MetadataConvertor;

if (!defined("ENDIANNESS")) {
    define("ENDIANNESS", (pack("s", 1) === "\0\1" ? Binary::BIG_ENDIAN : Binary::LITTLE_ENDIAN));
}

class Binary{
    const BIG_ENDIAN = 0x00;
    const LITTLE_ENDIAN = 0x01;

    private static function checkLength($str, $expect){
//		if(($len = strlen($str)) !== $expect) throw new \RuntimeException("Unexpected length: expected ".$expect.", got ".$len);
    }

    public static function signByte($value)
    {
        return $value << 56 >> 56;
    }

    public static function unsignByte($value)
    {
        return $value & 0xff;
    }

    public static function signShort($value)
    {
        return $value << 48 >> 48;
    }

    public static function unsignShort($value)
    {
        return $value & 0xffff;
    }

    public static function signInt($value)
    {
        return $value << 32 >> 32;
    }

    public static function unsignInt($value)
    {
        return $value & 0xffffffff;
    }

    public static function flipShortEndianness($value)
    {
        return self::readLShort(self::writeShort($value));
    }

    public static function flipIntEndianness($value)
    {
        return self::readLInt(self::writeInt($value));
    }

    public static function flipLongEndianness(int $value)
    {
        return self::readLLong(self::writeLong($value));
    }

    /**
     * Reads a 3-byte big-endian number
     *
     * @param $str
     *
     * @return mixed
     */
    public static function readTriad($str){
        self::checkLength($str, 3);
        return unpack("N", "\x00" . $str)[1];
    }

    /**
     * Writes a 3-byte big-endian number
     *
     * @param $value
     *
     * @return string
     */
    public static function writeTriad($value){
        return substr(pack("N", $value), 1);
    }

    /**
     * Reads a 3-byte little-endian number
     *
     * @param $str
     *
     * @return mixed
     */
    public static function readLTriad($str){
        self::checkLength($str, 3);
        return unpack("V", $str . "\x00")[1];
    }

    /**
     * Writes a 3-byte little-endian number
     *
     * @param $value
     *
     * @return string
     */
    public static function writeLTriad($value){
        return substr(pack("V", $value), 0, -1);
    }

    /**
     * Writes a coded metadata string
     *
     * @param array $data
     *
     * @return string
     */
    public static function writeMetadata(array $data, $playerProtocol){
        $data = MetadataConvertor::updateMeta($data, $playerProtocol);
        $m = "";
        $m .= self::writeVarInt(count($data));
        foreach($data as $bottom => $d){
            switch($d[0]){
                case Entity::DATA_TYPE_UNSIGNED_LONG:
                    $type = Entity::DATA_TYPE_LONG;
                    break;
                default:
                    $type = $d[0];
                    break;
            }
            $m .= self::writeVarInt($bottom);
            $m .= self::writeVarInt($type);
            switch($d[0]){
                case Entity::DATA_TYPE_BYTE:
                    $m .= self::writeByte($d[1]);
                    break;
                case Entity::DATA_TYPE_SHORT:
                    $m .= self::writeLShort($d[1]);
                    break;
                case Entity::DATA_TYPE_LONG:
                case Entity::DATA_TYPE_INT:
                    $m .= self::writeSignedVarInt($d[1]);
                    break;
                case Entity::DATA_TYPE_FLOAT:
                    $m .= self::writeLFloat($d[1]);
                    break;
                case Entity::DATA_TYPE_STRING:
                    $m .= self::writeVarInt(strlen($d[1])) . $d[1];
                    break;
                case Entity::DATA_TYPE_SLOT:
                    $m .= "\x7f";
//					$m .= self::writeLShort($d[1][0]);
//					$m .= self::writeByte($d[1][1]);
//					$m .= self::writeLShort($d[1][2]);
                    break;
                case Entity::DATA_TYPE_POS:
                    $m .= self::writeSignedVarInt($d[1][0]);
                    $m .= self::writeSignedVarInt($d[1][1]);
                    $m .= self::writeSignedVarInt($d[1][2]);
                    break;
                case Entity::DATA_TYPE_UNSIGNED_LONG:
                    $m .= self::writeVarInt($d[1]);
                    break;
                case Entity::DATA_TYPE_VECTOR3:
                    $m .= self::writeLFloat($d[1][0]);
                    $m .= self::writeLFloat($d[1][1]);
                    $m .= self::writeLFloat($d[1][2]);
                    break;
            }
        }
        return $m;
    }

    /**
     * Reads a metadata coded string
     *
     * @param      $value
     * @param bool $types
     *
     * @return array
     */
//	public static function readMetadata($value, $types = false){
//		$offset = 0;
//		$m = [];
//		$b = ord($value{$offset});
//		++$offset;
//		while($b !== 127 and isset($value{$offset})){
//			$bottom = $b & 0x1F;
//			$type = $b >> 5;
//			switch($type){
//				case Entity::DATA_TYPE_BYTE:
//					$r = self::readByte($value{$offset});
//					++$offset;
//					break;
//				case Entity::DATA_TYPE_SHORT:
//					$r = self::readLShort(substr($value, $offset, 2));
//					$offset += 2;
//					break;
//				case Entity::DATA_TYPE_INT:
//					$r = self::readLInt(substr($value, $offset, 4));
//					$offset += 4;
//					break;
//				case Entity::DATA_TYPE_FLOAT:
//					$r = self::readLFloat(substr($value, $offset, 4));
//					$offset += 4;
//					break;
//				case Entity::DATA_TYPE_STRING:
//					$len = self::readLShort(substr($value, $offset, 2));
//					$offset += 2;
//					$r = substr($value, $offset, $len);
//					$offset += $len;
//					break;
//				case Entity::DATA_TYPE_SLOT:
//					$r = [];
//					$r[] = self::readLShort(substr($value, $offset, 2));
//					$offset += 2;
//					$r[] = ord($value{$offset});
//					++$offset;
//					$r[] = self::readLShort(substr($value, $offset, 2));
//					$offset += 2;
//					break;
//				case Entity::DATA_TYPE_POS:
//					$r = [];
//					for($i = 0; $i < 3; ++$i){
//						$r[] = self::readLInt(substr($value, $offset, 4));
//						$offset += 4;
//					}
//					break;
//				case Entity::DATA_TYPE_LONG:
//					$r = self::readLLong(substr($value, $offset, 4));
//					$offset += 8;
//					break;
//				default:
//					return [];
//
//			}
//			if($types === true){
//				$m[$bottom] = [$r, $type];
//			}else{
//				$m[$bottom] = $r;
//			}
//			$b = ord($value{$offset});
//			++$offset;
//		}
//
//		return $m;
//	}

    /**
     * Reads a byte boolean
     *
     * @param $b
     *
     * @return bool
     */
    public static function readBool($b){
        return self::readByte($b, false) === 0 ? false : true;
    }

    /**
     * Writes a byte boolean
     *
     * @param $b
     *
     * @return bool|string
     */
    public static function writeBool($b){
        return $b ? "\x01" : "\x00";
    }

    /**
     * Reads an unsigned/signed byte
     *
     * @param string $c
     * @param bool   $signed
     *
     * @return int
     */
    public static function readByte($c)
    {
        return ord($c[0]);
    }

    /**
     * Reads a signed byte (-128 - 127)
     *
     * @param string $c
     *
     * @return int
     */
    public static function readSignedByte($c)
    {
        return self::signByte(ord($c[0]));
    }

    /**
     * Writes an unsigned/signed byte
     *
     * @param $c
     *
     * @return string
     */
    public static function writeByte($c){
        return chr($c);
    }

    /**
     * Reads a 16-bit unsigned big-endian number
     *
     * @param $str
     *
     * @return int
     */
    public static function readShort($str){
        self::checkLength($str, 2);
        return unpack("n", $str)[1];
    }

    /**
     * Reads a 16-bit signed big-endian number
     *
     * @param $str
     *
     * @return int
     */
    public static function readSignedShort($str){
        self::checkLength($str, 2);
        if(PHP_INT_SIZE === 8){
            return @unpack("n", $str)[1] << 48 >> 48;
        } elseif (PHP_INT_SIZE !== 8) {
            return unpack("n", $str)[1] << 16 >> 16;
        } else {
            return self::signShort(unpack("n", $str)[1]);
        }
    }

    /**
     * Writes a 16-bit signed/unsigned big-endian number
     *
     * @param $value
     *
     * @return string
     */
    public static function writeShort($value){
        return pack("n", $value);
    }

    /**
     * Reads a 16-bit unsigned little-endian number
     *
     * @param      $str
     *
     * @return int
     */
    public static function readLShort($str){
        self::checkLength($str, 2);
        return unpack("v", $str)[1];
    }

    /**
     * Reads a 16-bit signed little-endian number
     *
     * @param      $str
     *
     * @return int
     */
    public static function readSignedLShort($str){
        self::checkLength($str, 2);
        if(PHP_INT_SIZE === 8){
            return unpack("v", $str)[1] << 48 >> 48;
        } elseif (PHP_INT_SIZE !== 8) {
            return unpack("v", $str)[1] << 16 >> 16;
        } else {
            return self::signShort(unpack("v", $str)[1]);
        }
    }

    /**
     * Writes a 16-bit signed/unsigned little-endian number
     *
     * @param $value
     *
     * @return string
     */
    public static function writeLShort($value){
        return pack("v", $value);
    }

    public static function readInt($str){
        self::checkLength($str, 4);
        if(PHP_INT_SIZE === 8){
            return unpack("N", $str)[1] << 32 >> 32;
        } elseif (PHP_INT_SIZE !== 8) {
            return unpack("N", $str)[1];
        } else {
            return self::signInt(unpack("N", $str)[1]);
        }
    }

    public static function writeInt($value){
        return pack("N", $value);
    }

    public static function readLInt($str){
        self::checkLength($str, 4);
        if(PHP_INT_SIZE === 8){
            return unpack("V", $str)[1] << 32 >> 32;
        } elseif (PHP_INT_SIZE !== 8) {
            return unpack("V", $str)[1];
        } else {
            return self::signInt(unpack("V", $str)[1]);
        }
    }

    public static function writeLInt($value){
        return pack("V", $value);
    }

    public static function readFloat($str){
        self::checkLength($str, 4);
        if (ENDIANNESS === self::BIG_ENDIAN) {
            return unpack("f", $str)[1];
        } elseif (ENDIANNESS !== self::BIG_ENDIAN) {
            return unpack("f", strrev($str))[1];
        } else {
            return unpack("G", $str)[1];
        }
        //return ENDIANNESS === self::BIG_ENDIAN ? unpack("f", $str)[1] : unpack("f", strrev($str))[1];
    }

    /**
     * Writes a 4-byte floating-point number.
     *
     * @param float $value
     *
     * @return string
     */
    public static function writeFloat($value){
        if (ENDIANNESS === self::BIG_ENDIAN) {
            return pack("f", $value);
        } elseif (ENDIANNESS !== self::BIG_ENDIAN) {
            return strrev(pack("f", $value));
        } else {
            return pack("G", $value);
        }
        //return ENDIANNESS === self::BIG_ENDIAN ? pack("f", $value) : strrev(pack("f", $value));
    }

    /**
     * Reads a 4-byte little-endian floating-point number rounded to the specified number of decimal places.
     *
     * @param string $str
     * @param int $accuracy
     *
     * @return float
     */
    public static function readRoundedLFloat($str, $accuracy)
    {
        return round(self::readLFloat($str), $accuracy);
    }

    public static function readLFloat($str){
        self::checkLength($str, 4);
        return ENDIANNESS === self::BIG_ENDIAN ? unpack("f", strrev($str))[1] : unpack("f", $str)[1];
    }

    public static function writeLFloat($value){
        return pack("g", $value);
        //return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("f", $value)) : pack("f", $value);
    }

    public static function printFloat($value){
        return preg_replace("/(\\.\\d+?)0+$/", "$1", sprintf("%F", $value));
    }


    public static function readDouble($str){
        self::checkLength($str, 8);
        return unpack("E", $str)[1];
        //return ENDIANNESS === self::BIG_ENDIAN ? unpack("d", $str)[1] : unpack("d", strrev($str))[1];
    }

    public static function writeDouble($value){
        return pack("E", $value);
        //return ENDIANNESS === self::BIG_ENDIAN ? pack("d", $value) : strrev(pack("d", $value));
    }

    public static function readLDouble($str){
        self::checkLength($str, 8);
        return unpack("e", $str)[1];
        //return ENDIANNESS === self::BIG_ENDIAN ? unpack("d", strrev($str))[1] : unpack("d", $str)[1];
    }

    public static function writeLDouble($value){
        return pack("e", $value);
        //return ENDIANNESS === self::BIG_ENDIAN ? strrev(pack("d", $value)) : pack("d", $value);
    }

    public static function readLong($x){
        self::checkLength($x, 8);
        if (is_string($x)) {
            return unpack("J", $x)[1];
        }
        if(PHP_INT_SIZE === 8){
            $int = @unpack("N*", $x);
            return ($int[1] << 32) | $int[2];
        }else{
            $value = "0";
            for($i = 0; $i < 8; $i += 2){
                $value = bcmul($value, "65536", 0);
                $value = bcadd($value, self::readShort(substr($x, $i, 2)), 0);
            }

            if(bccomp($value, "9223372036854775807") == 1){
                $value = bcadd($value, "-18446744073709551616");
            }

            return $value;
        }
    }

    public static function writeLong($value){
        if (is_int($value)) {
            return pack("J", $value);
        }
        if(PHP_INT_SIZE === 8){
            return pack("NN", $value >> 32, $value & 0xFFFFFFFF);
        }else{
            $x = "";

            if(bccomp($value, "0") == -1){
                $value = bcadd($value, "18446744073709551616");
            }

            $x .= self::writeShort(bcmod(bcdiv($value, "281474976710656"), "65536"));
            $x .= self::writeShort(bcmod(bcdiv($value, "4294967296"), "65536"));
            $x .= self::writeShort(bcmod(bcdiv($value, "65536"), "65536"));
            $x .= self::writeShort(bcmod($value, "65536"));

            return $x;
        }
    }

    public static function readLLong($str){
        if (is_string($str)) {
            return unpack("P", $str)[1];
        }
        return self::readLong(strrev($str));
    }

    public static function writeLLong($value){
        if (is_int($value)) {
            return pack("P", $value);
        }
        return strrev(self::writeLong($value));
    }

    public static function writeSignedVarInt($v){
        if ($v >= 0) {
            $v = 2 * $v;
        } else {
            $v = 2 * abs($v) - 1;
        }
        return self::writeVarInt($v);
    }

    public static function newreadUnsignedVarInt(string $buffer, int &$offset) : int{
        $value = 0;
        for($i = 0; $i <= 28; $i += 7){
            if(!isset($buffer[$offset])){
                throw new \InvalidArgumentException("No bytes left in buffer");
            }
            $b = ord($buffer[$offset++]);
            $value |= (($b & 0x7f) << $i);

            if(($b & 0x80) === 0){
                return $value;
            }
        }

        throw new \InvalidArgumentException("VarInt did not terminate after 5 bytes!");
    }

    public static function readUnsignedVarInt($stream){
        $value = 0;
        $i = 0;
        do{
            if($i > 63){
                throw new \InvalidArgumentException("Varint did not terminate after 10 bytes!");
            }
            $value |= ((($b = $stream->getByte()) & 0x7f) << $i);
            $i += 7;
        }while($b & 0x80);
        return $value;
    }

    public static function writeUnsignedVarInt($value){
        $buf = "";
        for($i = 0; $i < 10; ++$i){
            if(($value >> 7) !== 0){
                $buf .= chr($value | 0x80); //Let chr() take the last byte of this, it's faster than adding another & 0x7f.
            }else{
                $buf .= chr($value & 0x7f);
                return $buf;
            }

            $value = (($value >> 7) & (PHP_INT_MAX >> 6)); //PHP really needs a logical right-shift operator
        }

        throw new \InvalidArgumentException("Value too large to be encoded as a varint");
    }

    public static function writeUnsignedVarLong($value)
    {
        self::writeUnsignedVarInt($value);
        throw new InvalidArgumentException("Value too large to be encoded as a VarLong");
    }


    public static function writeVarInt($v){
        if ($v < 0x80) {
            return chr($v);
        } else {
            $values = array();
            while ($v > 0) {
                $values[] = 0x80 | ($v & 0x7f);
                $v = $v >> 7;
            }
            $values[count($values)-1] &= 0x7f;
            $bytes = call_user_func_array('pack', array_merge(array('C*'), $values));
            return $bytes;
        }
    }

}

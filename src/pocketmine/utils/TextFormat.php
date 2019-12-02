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

/**
 * Class used to handle Minecraft chat format, and convert it to other formats like ANSI or HTML
 */
abstract class TextFormat{
	const ESCAPE = "\xc2\xa7"; //§

	const BLACK = "§0";
	const DARK_BLUE = "§1";
	const DARK_GREEN = "§2";
	const DARK_AQUA = "§3";
	const DARK_RED = "§4";
	const DARK_PURPLE = "§5";
	const GOLD = "§6";
	const GRAY = "§7";
	const DARK_GRAY = "§8";
	const BLUE = "§9";
	const GREEN = "§a";
	const AQUA = "§b";
	const RED = "§c";
	const LIGHT_PURPLE = "§d";
	const YELLOW = "§e";
	const WHITE = "§f";

	const OBFUSCATED = "§k";
	const BOLD = "§l";
	const STRIKETHROUGH = "§m";
	const UNDERLINE = "§n";
	const ITALIC = "§o";
	const RESET = "§r";
	
	const MODE_BOTH = 0;
	const MODE_LEFT = 1;
	const MODE_RIGHT = 2;
	
	const MODAL_LINE_PIXEL_LENGTH = 203;

	/**
	 * Splits the string by Format tokens
	 *
	 * @param string $string
	 *
	 * @return array
	 */
	public static function tokenize($string){
		return preg_split("/(§[0123456789abcdefklmnor])/", $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	}

	/**
	 * Cleans the string from Minecraft codes and ANSI Escape Codes
	 *
	 * @param string $string
	 * @param bool   $removeFormat
	 *
	 * @return mixed
	 */
	public static function clean($string, $removeFormat = true){
		if($removeFormat){
			$string = str_replace("§", "", preg_replace("/§[0123456789abcdefklmnor]/", "", $string));
		}
		return str_replace("\x1b", "", preg_replace("/\x1b[\\(\\][[0-9;\\[\\(]+[Bm]/", "", $string));
	}

	/**
	 * Returns an JSON-formatted string with colors/markup
	 *
	 * @param string|array $string
	 *
	 * @return string
	 */
	public static function toJSON($string){
		if(!is_array($string)){
			$string = self::tokenize($string);
		}
		$newString = [];
		$pointer =& $newString;
		$color = "white";
		$bold = false;
		$italic = false;
		$underlined = false;
		$strikethrough = false;
		$obfuscated = false;
		$index = 0;

		foreach($string as $token){
			if(isset($pointer["text"])){
				if(!isset($newString["extra"])){
					$newString["extra"] = [];
				}
				$newString["extra"][$index] = [];
				$pointer =& $newString["extra"][$index];
				if($color !== "white"){
					$pointer["color"] = $color;
				}
				if($bold !== false){
					$pointer["bold"] = true;
				}
				if($italic !== false){
					$pointer["italic"] = true;
				}
				if($underlined !== false){
					$pointer["underlined"] = true;
				}
				if($strikethrough !== false){
					$pointer["strikethrough"] = true;
				}
				if($obfuscated !== false){
					$pointer["obfuscated"] = true;
				}
				++$index;
			}
			switch($token){
				case TextFormat::BOLD:
					if($bold === false){
						$pointer["bold"] = true;
						$bold = true;
					}
					break;
				case TextFormat::OBFUSCATED:
					if($obfuscated === false){
						$pointer["obfuscated"] = true;
						$obfuscated = true;
					}
					break;
				case TextFormat::ITALIC:
					if($italic === false){
						$pointer["italic"] = true;
						$italic = true;
					}
					break;
				case TextFormat::UNDERLINE:
					if($underlined === false){
						$pointer["underlined"] = true;
						$underlined = true;
					}
					break;
				case TextFormat::STRIKETHROUGH:
					if($strikethrough === false){
						$pointer["strikethrough"] = true;
						$strikethrough = true;
					}
					break;
				case TextFormat::RESET:
					if($color !== "white"){
						$pointer["color"] = "white";
						$color = "white";
					}
					if($bold !== false){
						$pointer["bold"] = false;
						$bold = false;
					}
					if($italic !== false){
						$pointer["italic"] = false;
						$italic = false;
					}
					if($underlined !== false){
						$pointer["underlined"] = false;
						$underlined = false;
					}
					if($strikethrough !== false){
						$pointer["strikethrough"] = false;
						$strikethrough = false;
					}
					if($obfuscated !== false){
						$pointer["obfuscated"] = false;
						$obfuscated = false;
					}
					break;

				//Colors
				case TextFormat::BLACK:
					$pointer["color"] = "black";
					$color = "black";
					break;
				case TextFormat::DARK_BLUE:
					$pointer["color"] = "dark_blue";
					$color = "dark_blue";
					break;
				case TextFormat::DARK_GREEN:
					$pointer["color"] = "dark_green";
					$color = "dark_green";
					break;
				case TextFormat::DARK_AQUA:
					$pointer["color"] = "dark_aqua";
					$color = "dark_aqua";
					break;
				case TextFormat::DARK_RED:
					$pointer["color"] = "dark_red";
					$color = "dark_red";
					break;
				case TextFormat::DARK_PURPLE:
					$pointer["color"] = "dark_purple";
					$color = "dark_purple";
					break;
				case TextFormat::GOLD:
					$pointer["color"] = "gold";
					$color = "gold";
					break;
				case TextFormat::GRAY:
					$pointer["color"] = "gray";
					$color = "gray";
					break;
				case TextFormat::DARK_GRAY:
					$pointer["color"] = "dark_gray";
					$color = "dark_gray";
					break;
				case TextFormat::BLUE:
					$pointer["color"] = "blue";
					$color = "blue";
					break;
				case TextFormat::GREEN:
					$pointer["color"] = "green";
					$color = "green";
					break;
				case TextFormat::AQUA:
					$pointer["color"] = "aqua";
					$color = "aqua";
					break;
				case TextFormat::RED:
					$pointer["color"] = "red";
					$color = "red";
					break;
				case TextFormat::LIGHT_PURPLE:
					$pointer["color"] = "light_purple";
					$color = "light_purple";
					break;
				case TextFormat::YELLOW:
					$pointer["color"] = "yellow";
					$color = "yellow";
					break;
				case TextFormat::WHITE:
					$pointer["color"] = "white";
					$color = "white";
					break;
				default:
					$pointer["text"] = $token;
					break;
			}
		}

		if(isset($newString["extra"])){
			foreach($newString["extra"] as $k => $d){
				if(!isset($d["text"])){
					unset($newString["extra"][$k]);
				}
			}
		}

		return \json_encode($newString, JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Returns an HTML-formatted string with colors/markup
	 *
	 * @param string|array $string
	 *
	 * @return string
	 */
	public static function toHTML($string){
		if(!is_array($string)){
			$string = self::tokenize($string);
		}
		$newString = "";
		$tokens = 0;
		foreach($string as $token){
			switch($token){
				case TextFormat::BOLD:
					$newString .= "<span style=font-weight:bold>";
					++$tokens;
					break;
				case TextFormat::OBFUSCATED:
					//$newString .= "<span style=text-decoration:line-through>";
					//++$tokens;
					break;
				case TextFormat::ITALIC:
					$newString .= "<span style=font-style:italic>";
					++$tokens;
					break;
				case TextFormat::UNDERLINE:
					$newString .= "<span style=text-decoration:underline>";
					++$tokens;
					break;
				case TextFormat::STRIKETHROUGH:
					$newString .= "<span style=text-decoration:line-through>";
					++$tokens;
					break;
				case TextFormat::RESET:
					$newString .= str_repeat("</span>", $tokens);
					$tokens = 0;
					break;

				//Colors
				case TextFormat::BLACK:
					$newString .= "<span style=color:#000>";
					++$tokens;
					break;
				case TextFormat::DARK_BLUE:
					$newString .= "<span style=color:#00A>";
					++$tokens;
					break;
				case TextFormat::DARK_GREEN:
					$newString .= "<span style=color:#0A0>";
					++$tokens;
					break;
				case TextFormat::DARK_AQUA:
					$newString .= "<span style=color:#0AA>";
					++$tokens;
					break;
				case TextFormat::DARK_RED:
					$newString .= "<span style=color:#A00>";
					++$tokens;
					break;
				case TextFormat::DARK_PURPLE:
					$newString .= "<span style=color:#A0A>";
					++$tokens;
					break;
				case TextFormat::GOLD:
					$newString .= "<span style=color:#FA0>";
					++$tokens;
					break;
				case TextFormat::GRAY:
					$newString .= "<span style=color:#AAA>";
					++$tokens;
					break;
				case TextFormat::DARK_GRAY:
					$newString .= "<span style=color:#555>";
					++$tokens;
					break;
				case TextFormat::BLUE:
					$newString .= "<span style=color:#55F>";
					++$tokens;
					break;
				case TextFormat::GREEN:
					$newString .= "<span style=color:#5F5>";
					++$tokens;
					break;
				case TextFormat::AQUA:
					$newString .= "<span style=color:#5FF>";
					++$tokens;
					break;
				case TextFormat::RED:
					$newString .= "<span style=color:#F55>";
					++$tokens;
					break;
				case TextFormat::LIGHT_PURPLE:
					$newString .= "<span style=color:#F5F>";
					++$tokens;
					break;
				case TextFormat::YELLOW:
					$newString .= "<span style=color:#FF5>";
					++$tokens;
					break;
				case TextFormat::WHITE:
					$newString .= "<span style=color:#FFF>";
					++$tokens;
					break;
				default:
					$newString .= $token;
					break;
			}
		}

		$newString .= str_repeat("</span>", $tokens);

		return $newString;
	}

	/**
	 * Returns a string with colorized ANSI Escape codes
	 *
	 * @param $string
	 *
	 * @return string
	 */
	public static function toANSI($string){
		if(!is_array($string)){
			$string = self::tokenize($string);
		}

		$newString = "";
		foreach($string as $token){
			switch($token){
				case TextFormat::BOLD:
					$newString .= Terminal::$FORMAT_BOLD;
					break;
				case TextFormat::OBFUSCATED:
					$newString .= Terminal::$FORMAT_OBFUSCATED;
					break;
				case TextFormat::ITALIC:
					$newString .= Terminal::$FORMAT_ITALIC;
					break;
				case TextFormat::UNDERLINE:
					$newString .= Terminal::$FORMAT_UNDERLINE;
					break;
				case TextFormat::STRIKETHROUGH:
					$newString .= Terminal::$FORMAT_STRIKETHROUGH;
					break;
				case TextFormat::RESET:
					$newString .= Terminal::$FORMAT_RESET;
					break;

				//Colors
				case TextFormat::BLACK:
					$newString .= Terminal::$COLOR_BLACK;
					break;
				case TextFormat::DARK_BLUE:
					$newString .= Terminal::$COLOR_DARK_BLUE;
					break;
				case TextFormat::DARK_GREEN:
					$newString .= Terminal::$COLOR_DARK_GREEN;
					break;
				case TextFormat::DARK_AQUA:
					$newString .= Terminal::$COLOR_DARK_AQUA;
					break;
				case TextFormat::DARK_RED:
					$newString .= Terminal::$COLOR_DARK_RED;
					break;
				case TextFormat::DARK_PURPLE:
					$newString .= Terminal::$COLOR_PURPLE;
					break;
				case TextFormat::GOLD:
					$newString .= Terminal::$COLOR_GOLD;
					break;
				case TextFormat::GRAY:
					$newString .= Terminal::$COLOR_GRAY;
					break;
				case TextFormat::DARK_GRAY:
					$newString .= Terminal::$COLOR_DARK_GRAY;
					break;
				case TextFormat::BLUE:
					$newString .= Terminal::$COLOR_BLUE;
					break;
				case TextFormat::GREEN:
					$newString .= Terminal::$COLOR_GREEN;
					break;
				case TextFormat::AQUA:
					$newString .= Terminal::$COLOR_AQUA;
					break;
				case TextFormat::RED:
					$newString .= Terminal::$COLOR_RED;
					break;
				case TextFormat::LIGHT_PURPLE:
					$newString .= Terminal::$COLOR_LIGHT_PURPLE;
					break;
				case TextFormat::YELLOW:
					$newString .= Terminal::$COLOR_YELLOW;
					break;
				case TextFormat::WHITE:
					$newString .= Terminal::$COLOR_WHITE;
					break;
				default:
					$newString .= $token;
					break;
			}
		}

		return $newString;
	}
	
	private static $charPixelLength = [
		'default' => 5,
		' ' => 3,
		'I' => 3,
		'f' => 4,
		'i' => 1,
		'j' => 4,
		'k' => 4,
		'l' => 2,
		't' => 3,
		'!' => 1,
		'@' => 6,
		'*' => 4,
		'(' => 4,
		')' => 4,
		'[' => 3,
		']' => 3,
		'{' => 4,
		'}' => 4,
		':' => 1,
		';' => 1,
		'\'' => 2,
		'"' => 4,
		',' => 1,
		'.' => 1,
		'<' => 4,
		'>' => 4,
		'|' => 1,
		'`' => 2,
		'~' => 6,
	];
	const SPACE_PIXEL_LENGTH = 3;
	
	public static function centerLatinText($lines) {
		$pixelLengths = [];
		foreach ($lines as $index => $line) {
			$linePixelLength = 0;
			$line = TextFormat::clean($line);
			$lineLength = strlen($line);
			for ($i = 0; $i < $lineLength; $i++) {
				$linePixelLength += isset(static::$charPixelLength[$line[$i]]) ? static::$charPixelLength[$line[$i]] : static::$charPixelLength['default'];
			}
			// don't know why i made this
//			$pixelLengths[$index] = $linePixelLength + $lineLength + ($lineLength == 1 ? 0 : $lineLength - 1);
			$pixelLengths[$index] = $linePixelLength;
		}
		$maxLength = max($pixelLengths);
		foreach ($pixelLengths as $index => $pixelLength) {
			$spaceNumOnOneSide = intval(($maxLength - $pixelLength) / static::SPACE_PIXEL_LENGTH) >> 1;
			$lines[$index] = str_repeat(" ", $spaceNumOnOneSide) . $lines[$index];
		}
		return $lines;
	}
	
	public static function createTable($data, $columsProportions) {
		$portions = array_sum($columsProportions);
		$portionPixelLength = floor(static::MODAL_LINE_PIXEL_LENGTH / $portions);
		$result = "";
		$residue = 0;
		foreach ($data as $values) {
			foreach ($values as $key => $value) {
				if (!isset($columsProportions[$key])) {
					continue;
				}
				$columnMaxPixelLength = $columsProportions[$key] * $portionPixelLength + $residue;
				$column = static::addSpaces($value, $columnMaxPixelLength);
//				$residue = $columnMaxPixelLength - static::getStringPixelLength($column, true);
				$result .= $column;
			}
			$result .= "\n";
		}
		return $result;
	}
	
	public static function getStringPixelLength($string, $withLetterSpacing = false) {
		$linePixelLength = 0;
		$length = strlen($string);
		for ($i = 0; $i < $length; $i++) {
			$linePixelLength += isset(static::$charPixelLength[$string[$i]]) ? static::$charPixelLength[$string[$i]] : static::$charPixelLength['default'];
		}
		if ($withLetterSpacing) {
			$linePixelLength += $length - 1;
		}
		return $linePixelLength;
	}
	
	public static function addSpaces($string, $resPixelLength, $mode = self::MODE_BOTH) {
		$stringLength = static::getStringPixelLength($string, true);
		if ($resPixelLength < $stringLength) {
			return $string;
		}
		$padNum = floor(($resPixelLength - $stringLength) / (static::$charPixelLength[' '] + 1)) + strlen($string);
		switch ($mode) {
			case static::MODE_BOTH:
				return str_pad($string, $padNum, " ", STR_PAD_BOTH);
			case static::MODE_LEFT:
				return str_pad($string, $padNum, " ", STR_PAD_LEFT);
			case static::MODE_RIGHT:
				return str_pad($string, $padNum, " ", STR_PAD_RIGHT);
		}
		return $string;
	}

}
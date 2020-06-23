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

namespace pocketmine\network\protocol;

use pocketmine\network\multiversion\MultiversionEnums;
use pocketmine\network\protocol\Info;
use pocketmine\utils\BinaryStream;

class AvailableCommandsPacket extends PEPacket{
	const NETWORK_ID = Info::AVAILABLE_COMMANDS_PACKET;
	const PACKET_NAME = "AVAILABLE_COMMANDS_PACKET";
	
	static private $commandsBuffer = [];
	static private $commandsBufferDefault = "";
	
	public $commands;
	
	public function decode($playerProtocol){
	}
	
	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		foreach (self::$commandsBuffer as $protocol => $data) {
			if ($playerProtocol >= $protocol) {
				$this->put($data);
				return;
			}
		}
		$this->putString(self::$commandsBufferDefault);
	}
	
	const ARG_FLAG_VALID = 0x100000;
	const ARG_FLAG_ENUM = 0x200000;
	
	const ARG_TYPE_INT = "ARG_TYPE_INT";
	const ARG_TYPE_FLOAT = "ARG_TYPE_FLOAT";
	const ARG_TYPE_VALUE = "ARG_TYPE_VALUE";
	const ARG_TYPE_TARGET = "ARG_TYPE_TARGET";
	const ARG_TYPE_STRING = "ARG_TYPE_STRING";
	const ARG_TYPE_POSITION = "ARG_TYPE_POSITION";
	const ARG_TYPE_RAWTEXT = "ARG_TYPE_RAWTEXT";
	const ARG_TYPE_TEXT = "ARG_TYPE_TEXT";
	const ARG_TYPE_JSON = "ARG_TYPE_JSON";
	const ARG_TYPE_COMMAND = "ARG_TYPE_COMMAND";
	
	public static function prepareCommands($commands) {
		self::$commandsBufferDefault = json_encode($commands);
		
		$enumValues = [];
		$enumValuesCount = 0;
		$enums = [];
		$enumsCount = 0;
		$commandsStreams = [
			Info::PROTOCOL_120 => new BinaryStream(),
			Info::PROTOCOL_271 => new BinaryStream(),
			Info::PROTOCOL_280 => new BinaryStream(),
			Info::PROTOCOL_340 => new BinaryStream(),
			Info::PROTOCOL_342 => new BinaryStream(),
			Info::PROTOCOL_350 => new BinaryStream(),
			Info::PROTOCOL_351 => new BinaryStream(),
			Info::PROTOCOL_354 => new BinaryStream(),
			Info::PROTOCOL_360 => new BinaryStream(),
			Info::PROTOCOL_361 => new BinaryStream(),
			Info::PROTOCOL_370 => new BinaryStream(),
			Info::PROTOCOL_385 => new BinaryStream(),
			Info::PROTOCOL_386 => new BinaryStream(),
			Info::PROTOCOL_389 => new BinaryStream(),
			Info::PROTOCOL_390 => new BinaryStream(),
			Info::PROTOCOL_392 => new BinaryStream(),
			Info::PROTOCOL_393 => new BinaryStream(),
			Info::PROTOCOL_400 => new BinaryStream(),
			Info::PROTOCOL_406 => new BinaryStream(),
			Info::PROTOCOL_407 => new BinaryStream(),
		];
		
		foreach ($commands as $commandName => &$commandData) { // Replace &$commandData with $commandData when alises fix for 1.2 won't be needed anymore
			$commandsStream = new BinaryStream();
			if ($commandName == 'help') { //temp fix for 1.2
				unset($commands[$commandName]);
				continue;
			}
			$commandsStream->putString($commandName);
			$commandsStream->putString($commandData['versions'][0]['description']);
			$commandsStream->putByte(0); // flags
			$permission = AdventureSettingsPacket::COMMAND_PERMISSION_LEVEL_ANY;
			switch ($commandData['versions'][0]['permission']) {
				case "staff":
					$permission = AdventureSettingsPacket::COMMAND_PERMISSION_LEVEL_GAME_MASTERS;
					default;
			}
			$commandsStream->putByte($permission); // permission level
			if (isset($commandData['versions'][0]['aliases']) && !empty($commandData['versions'][0]['aliases'])) {
				foreach ($commandData['versions'][0]['aliases'] as $alias) {
					$aliasAsCommand = $commandData;
					$aliasAsCommand['versions'][0]['aliases'] = [];
					$commands[$alias] = $aliasAsCommand;
				}
				$commandData['versions'][0]['aliases'] = [];
			}
			$aliasesEnumId = -1; // temp aliases fix for 1.2
			$commandsStream->putLInt($aliasesEnumId);
			$commandsStream->putVarInt(count($commandData['versions'][0]['overloads'])); // overloads
			/** @IMPORTANT $commandsStream doesn't should use after this line */
			foreach ($commandsStreams as $protocol => $unused) {
				$commandsStreams[$protocol]->put($commandsStream->getBuffer());
			}
			foreach ($commandData['versions'][0]['overloads'] as $overloadData) {
				$paramNum = count($overloadData['input']['parameters']);
				foreach ($commandsStreams as $protocol => $unused) {
					$commandsStreams[$protocol]->putVarInt($paramNum);
				}
				foreach ($overloadData['input']['parameters'] as $paramData) {
					// rawtext type cause problems on some types of clients
					$isParamOneAndOptional = ($paramNum == 1 && isset($paramData['optional']) && $paramData['optional']);
					if ($paramData['type'] == "rawtext" && ($paramNum > 1 || $isParamOneAndOptional)) {
						$paramData['type'] = "string";
					}
					if ($paramData['type'] == "stringenum") {
						 $enums[$enumsCount]['name'] = $paramData['name'];
						 $enums[$enumsCount]['data'] = [];
						 foreach ($paramData['enum_values'] as $enumElem) {
							 $enumValues[$enumValuesCount] = $enumElem;
							 $enums[$enumsCount]['data'][] = $enumValuesCount;
							 $enumValuesCount++;
						 }
						 $enumsCount++;
                    }
					foreach ($commandsStreams as $protocol => $unused) {
						$commandsStreams[$protocol]->putString($paramData['name']);
						 if ($paramData['type'] == "stringenum") {
                            $commandsStreams[$protocol]->putLInt(self::ARG_FLAG_VALID | self::ARG_FLAG_ENUM | ($enumsCount - 1));
                        } else {
							$commandsStreams[$protocol]->putLInt(self::ARG_FLAG_VALID | self::getFlag($paramData['type'], $protocol));
                        }
						$commandsStreams[$protocol]->putByte(isset($paramData['optional']) && $paramData['optional']);
						if ($protocol == Info::PROTOCOL_340 || $protocol >= Info::PROTOCOL_350) {
							$commandsStreams[$protocol]->putByte(0);
						}
					}
				}
			}
		}
		
		$additionalDataStream = new BinaryStream();
		$additionalDataStream->putVarInt($enumValuesCount);
		for ($i = 0; $i < $enumValuesCount; $i++) {
			$additionalDataStream->putString($enumValues[$i]);
		}
		$additionalDataStream->putVarInt(0);
		$additionalDataStream->putVarInt($enumsCount);
		for ($i = 0; $i < $enumsCount; $i++) {
			$additionalDataStream->putString($enums[$i]['name']);
			$dataCount = count($enums[$i]['data']);
			$additionalDataStream->putVarInt($dataCount);
			for ($j = 0; $j < $dataCount; $j++) {
				if ($enumValuesCount < 256) {
					$additionalDataStream->putByte($enums[$i]['data'][$j]);
				} else if ($enumValuesCount < 65536) {
					$additionalDataStream->putLShort($enums[$i]['data'][$j]);
				} else {
					$additionalDataStream->putLInt($enums[$i]['data'][$j]);
				}	
			}
		}
		$additionalDataStream->putVarInt(count($commands));
		
		foreach ($commandsStreams as $protocol => $commandsStream) {
			if ($protocol >= Info::PROTOCOL_280) {
				$commandsStream->putVarInt(0);
			}
			if ($protocol >= Info::PROTOCOL_385) {
				$commandsStream->putVarInt(0);
			}
			self::$commandsBuffer[$protocol] = $additionalDataStream->getBuffer() . $commandsStream->getBuffer();
		}
		
		krsort(self::$commandsBuffer);
	}
	
	/**
	 * @param string $paramName
	 * @return int
	 */
    private static function getFlag($paramName, $protocol){
		// new in 1.6
		// 05 - operator
	    $typeName = "";
	    switch ($paramName){
		    case "int":
				$typeName = self::ARG_TYPE_INT;
			    break;
		    case "float":
			    $typeName = self::ARG_TYPE_FLOAT;
			    break;
		    case "mixed":
		    case "value":
			    $typeName = self::ARG_TYPE_VALUE;
			    break;
		    case "target":
			    $typeName = self::ARG_TYPE_TARGET;
			    break;
		    case "string":
			    $typeName = self::ARG_TYPE_STRING;
			    break;
		    case "xyz":
		    case "x y z":
			    $typeName = self::ARG_TYPE_POSITION;
			    break;
		    case "rawtext":
		    case "message":
			    $typeName = self::ARG_TYPE_RAWTEXT;
			    break;
		    case "text":
			    $typeName = self::ARG_TYPE_TEXT;
			    break;
		    case "json":
			    $typeName = self::ARG_TYPE_JSON;
			    break;
		    case "command":
			    $typeName = self::ARG_TYPE_COMMAND;
			    break;
		    default:
			    return 0;
	    }
	    return MultiversionEnums::getCommandArgType($typeName, $protocol);
    }
}
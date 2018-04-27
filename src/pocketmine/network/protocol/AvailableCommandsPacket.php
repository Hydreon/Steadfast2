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

use pocketmine\network\protocol\Info;
use pocketmine\utils\BinaryStream;

class AvailableCommandsPacket extends PEPacket{
	const NETWORK_ID = Info::AVAILABLE_COMMANDS_PACKET;
	const PACKET_NAME = "AVAILABLE_COMMANDS_PACKET";
	
	static private $commandsBuffer = [];
	
	public $commands;
	
	public function decode($playerProtocol){
	}
	
	public function encode($playerProtocol){
		$this->reset($playerProtocol);
		if (isset(self::$commandsBuffer[$playerProtocol])) {
			$this->put(self::$commandsBuffer[$playerProtocol]);
		} else {
			$this->putString(self::$commandsBuffer['default']);
		}
	}
	
	const ARG_FLAG_VALID = 0x100000;
	const ARG_FLAG_ENUM = 0x200000;
	const ARG_TYPE_INT      = 0x01;
	const ARG_TYPE_FLOAT    = 0x02;
	const ARG_TYPE_VALUE    = 0x03;
	const ARG_TYPE_TARGET   = 0x04;
	const ARG_TYPE_STRING   = 0x0c;
	const ARG_TYPE_POSITION = 0x0d;
	const ARG_TYPE_RAWTEXT  = 0x10;
	const ARG_TYPE_TEXT     = 0x12;
	const ARG_TYPE_JSON     = 0x15;
	const ARG_TYPE_COMMAND  = 0x1c;
	
	public static function prepareCommands($commands) {
		self::$commandsBuffer['default'] = json_encode($commands);
		
		$enumValues = [];
		$enumValuesCount = 0;
		$enumAdditional = [];
		$enums = [];
		$commandsStream = new BinaryStream();
		foreach ($commands as $commandName => &$commandData) { // Replace &$commandData with $commandData when alises fix for 1.2 won't be needed anymore
			if ($commandName == 'help') { //temp fix for 1.2
				continue;
			}
			$commandsStream->putString($commandName);
			$commandsStream->putString($commandData['versions'][0]['description']);
			$commandsStream->putByte(0); // flags
			$commandsStream->putByte(0); // permission level
//			if (isset($commandData['versions'][0]['aliases']) && !empty($commandData['versions'][0]['aliases'])) {
//				$aliases = [];
//				foreach ($commandData['versions'][0]['aliases'] as $alias) {
//					if (!isset($enumAdditional[$alias])) {
//						$enumValues[$enumValuesCount] = $alias;
//						$enumAdditional[$alias] = $enumValuesCount;
//						$targetIndex = $enumValuesCount;
//						$enumValuesCount++;
//					} else {
//						$targetIndex = $enumAdditional[$alias];
//					}
//					$aliases[] = $targetIndex;
//				}
//				$enums[] = [
//					'name' => $commandName . 'CommandAliases',
//					'data' => $aliases,
//				];
//				$aliasesEnumId = count($enums) - 1;
//			} else {
//				$aliasesEnumId = -1;
//			}
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
			foreach ($commandData['versions'][0]['overloads'] as $overloadData) {
				$commandsStream->putVarInt(count($overloadData['input']['parameters']));
				$paramNum = count($overloadData['input']['parameters']);
				foreach ($overloadData['input']['parameters'] as $paramData) {
					$commandsStream->putString($paramData['name']);
					// rawtext type cause problems on some types of clients
					$isParamOneAndOptional = ($paramNum == 1 && isset($paramData['optional']) && $paramData['optional']);
					if ($paramData['type'] == "rawtext" && ($paramNum > 1 || $isParamOneAndOptional)) {
						$commandsStream->putLInt(self::ARG_FLAG_VALID | self::getFlag('string'));
					} else {
						$commandsStream->putLInt(self::ARG_FLAG_VALID | self::getFlag($paramData['type']));
					}
					$commandsStream->putByte(isset($paramData['optional']) && $paramData['optional']);
				}
			}
		}
		
		$additionalDataStream = new BinaryStream();
		$additionalDataStream->putVarInt($enumValuesCount);
		for ($i = 0; $i < $enumValuesCount; $i++) {
			$additionalDataStream->putString($enumValues[$i]);
		}
		$additionalDataStream->putVarInt(0);
		$enumsCount = count($enums);
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
		$additionalDataStream->put($commandsStream->buffer);
		self::$commandsBuffer[Info::PROTOCOL_120] = $additionalDataStream->buffer;
		self::$commandsBuffer[Info::PROTOCOL_200] = $additionalDataStream->buffer;
		self::$commandsBuffer[Info::PROTOCOL_220] = $additionalDataStream->buffer;
		self::$commandsBuffer[Info::PROTOCOL_221] = $additionalDataStream->buffer;
		self::$commandsBuffer[Info::PROTOCOL_240] = $additionalDataStream->buffer;
		self::$commandsBuffer[Info::PROTOCOL_260] = $additionalDataStream->buffer;
	}
	
	/**
     	 * @param string $paramName
     	 * @return int
     	 */
    	private static function getFlag($paramName){
            switch ($paramName){
            	case "int":
			    return self::ARG_TYPE_INT;
            	case "float":
                	return self::ARG_TYPE_FLOAT;
            	case "mixed":
                	return self::ARG_TYPE_VALUE;
            	case "target":
               		return self::ARG_TYPE_TARGET;
            	case "string":
                	return self::ARG_TYPE_STRING;
            	case "xyz":
                	return self::ARG_TYPE_POSITION;
            	case "rawtext":
                	return self::ARG_TYPE_RAWTEXT;
            	case "text":
                	return self::ARG_TYPE_TEXT;
            	case "json":
                	return self::ARG_TYPE_JSON;
            	case "command":
                	return self::ARG_TYPE_COMMAND;
            }
            return 0;
    }
}

<?php

namespace pocketmine\network\multiversion;

abstract class MultiversionEnums {

	private static $playerActionType = [
		'default' => [
			-1 => 'UNKNOWN',
			0 => 'START_DESTROY_BLOCK',
			1 => 'ABORT_DESTROY_BLOCK',
			2 => 'STOP_DESTROY_BLOCK',
			3 => 'GET_UPDATED_BLOCK',
			4 => 'DROP_ITEM',
			5 => 'RELEASE_USE_ITEM',
			6 => 'STOP_SLEEPENG',
			7 => 'RESPAWN',
			8 => 'START_JUMP',
			9 => 'START_SPRINTING',
			10 => 'STOP_STRINTING',
			11 => 'START_SNEAKING',
			12 => 'STOP_SNEAKING',
			13 => 'CHANGE_DEMENSION',
			14 => 'CHANGE_DEMENSION_ACK',
			15 => 'START_GLIDING',
			16 => 'STOP_GLIDING',
			17 => 'DENY_DESTROY_BLOCK',
			18 => 'CRACK_BLOCK',
		],
		'120' => [
			-1 => 'UNKNOWN',
			0 => 'START_DESTROY_BLOCK',
			1 => 'ABORT_DESTROY_BLOCK',
			2 => 'STOP_DESTROY_BLOCK',
			3 => 'GET_UPDATED_BLOCK',
			4 => 'DROP_ITEM',
			5 => 'STOP_SLEEPENG',
			6 => 'RESPAWN',
			7 => 'START_JUMP',
			8 => 'START_SPRINTING',
			9 => 'STOP_STRINTING',
			10 => 'START_SNEAKING',
			11 => 'STOP_SNEAKING',
			12 => 'CHANGE_DEMENSION',
			13 => 'CHANGE_DEMENSION_ACK',
			14 => 'START_GLIDING',
			15 => 'STOP_GLIDING',
			16 => 'DENY_DESTROY_BLOCK',
			17 => 'CRACK_BLOCK',
			18 => 'CHANGE_SKIN',
		],
	];
	
	private static $textPacketType = [
		'default' => [
			0 => 'TYPE_RAW',
			1 => 'TYPE_CHAT',
			2 => 'TYPE_TRANSLATION',
			3 => 'TYPE_POPUP',
			4 => 'TYPE_TIP',
			5 => 'TYPE_SYSTEM',
			6 => 'TYPE_WHISPER',
			7 => 'TYPE_ANNOUNCEMENT',
		],
		'120' => [
			0 => 'TYPE_RAW',
			1 => 'TYPE_CHAT',
			2 => 'TYPE_TRANSLATION',
			3 => 'TYPE_POPUP',
			4 => 'JUKEBOX_POPUP',
			5 => 'TYPE_TIP',
			6 => 'TYPE_SYSTEM',
			7 => 'TYPE_WHISPER',
			8 => 'TYPE_ANNOUNCEMENT',
		],
	];
	
	public static function getPlayerAction($playerProtocol, $actionId) {
		if (!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = 'default';
		}
		if (!isset(self::$playerActionType[$playerProtocol][$actionId])) {
			return self::$playerActionType[$playerProtocol][-1];
		}
		return self::$playerActionType[$playerProtocol][$actionId];
	}
	
	public static function getPlayerActionId($playerProtocol, $actionName) {
		if (!isset(self::$playerActionType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach (self::$playerActionType[$playerProtocol] as $key => $value) {
			if ($value == $actionName) {
				return $key;
			}
		}
		return -1;
	}
	
	public static function getMessageType($playerProtocol, $typeId) {
		if (!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = 'default';
		}
		if (!isset(self::$textPacketType[$playerProtocol][$typeId])) {
			return self::$textPacketType[$playerProtocol][0];
		}
		return self::$textPacketType[$playerProtocol][$typeId];
	}
	
	public static function getMessageTypeId($playerProtocol, $typeName) {
		if (!isset(self::$textPacketType[$playerProtocol])) {
			$playerProtocol = "default";
		}
		foreach (self::$textPacketType[$playerProtocol] as $key => $value) {
			if ($value == $typeName) {
				return $key;
			}
		}
		return 0;
	}
	
}

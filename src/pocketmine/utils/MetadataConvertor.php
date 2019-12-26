<?php

namespace pocketmine\utils;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\Info;

class MetadataConvertor {

	private static $initialMeta = [];
	
	private static $diffEntityFlags120 = [
		'DATA_FLAG_RESTING_BAT' => 22,
		'DATA_FLAG_ANIMAL_SIT' => 23,
		'DATA_FLAG_ANGRY_WOLF' => 24,
		'DATA_FLAG_INTERESTED' => 25,
		'DATA_FLAG_ANGRY_BLAZE' => 26,
		'DATA_FLAG_TAME_WOLF' => 27,
		'DATA_FLAG_LEASHED' => 28,
		'DATA_FLAG_SHAVED_SHIP' => 29,
		'DATA_FLAG_FALL_FLYING' => 30,
		'DATA_FLAG_ELDER_GUARDIAN' => 31,
		'DATA_FLAG_MOVING' => 32,
		'DATA_FLAG_NOT_IN_WATER' => 33,
		'DATA_FLAG_CHESTED_MOUNT' => 34,
		'DATA_FLAG_STACKABLE' => 35,
	];
	private static $diffEntityFlags221 = [
		'DATA_FLAG_RESTING_BAT' => 23,
		'DATA_FLAG_ANIMAL_SIT' => 24,
		'DATA_FLAG_ANGRY_WOLF' => 25,
		'DATA_FLAG_INTERESTED' => 26,
		'DATA_FLAG_ANGRY_BLAZE' => 27,
		'DATA_FLAG_TAME_WOLF' => 28,
		'DATA_FLAG_LEASHED' => 29,
		'DATA_FLAG_SHAVED_SHIP' => 30,
		'DATA_FLAG_FALL_FLYING' => 31,
		'DATA_FLAG_ELDER_GUARDIAN' => 32,
		'DATA_FLAG_MOVING' => 33,
		'DATA_FLAG_NOT_IN_WATER' => 34,
		'DATA_FLAG_CHESTED_MOUNT' => 35,
		'DATA_FLAG_STACKABLE' => 36,
		'DATA_FLAG_IS_WASD_CONTROLLED' => 43,
		'DATA_FLAG_CAN_POWER_JUMP' => 44,
		'DATA_FLAG_HAS_COLLISION' => 46,
		'DATA_FLAG_AFFECTED_BY_GRAVITY' => 47,
		'DATA_FLAG_FIRE_IMMUNE' => 48,
	];
	private static $diffEntityFlags290 = [
		'DATA_FLAG_RESTING_BAT' => 23,
		'DATA_FLAG_ANIMAL_SIT' => 24,
		'DATA_FLAG_ANGRY_WOLF' => 25,
		'DATA_FLAG_INTERESTED' => 26,
		'DATA_FLAG_ANGRY_BLAZE' => 27,
		'DATA_FLAG_TAME_WOLF' => 28,
		'DATA_FLAG_LEASHED' => 30,
		'DATA_FLAG_SHAVED_SHIP' => 31,
		'DATA_FLAG_FALL_FLYING' => 32,
		'DATA_FLAG_ELDER_GUARDIAN' => 33,
		'DATA_FLAG_MOVING' => 34,
		'DATA_FLAG_NOT_IN_WATER' => 35,
		'DATA_FLAG_CHESTED_MOUNT' => 36,
		'DATA_FLAG_STACKABLE' => 37,
		'DATA_FLAG_IS_WASD_CONTROLLED' => 44,
		'DATA_FLAG_CAN_POWER_JUMP' => 45,
		'DATA_FLAG_HAS_COLLISION' => 47,
		'DATA_FLAG_AFFECTED_BY_GRAVITY' => 48,
		'DATA_FLAG_FIRE_IMMUNE' => 49,
	];
	private static $entityFlags120 = [];
	private static $entityFlags221 = [];
	private static $entityFlags290 = [];
	
	private static $diffEntityMetaIds120 = [
		'DATA_MAX_AIR' => 43,
	];

	private static $diffEntityMetaIds220 = [
		'DATA_PLAYER_FLAGS' => 26,
		'DATA_PLAYER_BED_POSITION' => 28,
		'DATA_LEAD_HOLDER' => 37,
		'DATA_SCALE' => 38,
		'DATA_BUTTON_TEXT' => 39,
		'DATA_MAX_AIR' => 42,
		'DATA_WIDTH' => 53,
		'DATA_HEIGHT' => 54,
		'DATA_EXPLODE_TIMER' => 55,
		'DATA_SEAT_RIDER_OFFSET' => 56,
	];
	private static $diffEntityMetaIds221 = [
		'DATA_PLAYER_FLAGS' => 26,
		'DATA_PLAYER_BED_POSITION' => 28,
		'DATA_LEAD_HOLDER' => 37,
		'DATA_SCALE' => 38,
		'DATA_BUTTON_TEXT' => 39,
		'DATA_MAX_AIR' => 42,
		'DATA_WIDTH' => 53,
		'DATA_HEIGHT' => 54,
		'DATA_EXPLODE_TIMER' => 55,
		'DATA_SEAT_RIDER_OFFSET' => 56,
		'DATA_POSE_INDEX' => 78,
	];
	
	private static $diffEntityMetaIds340 = [
		'DATA_PLAYER_FLAGS' => 26,
		'DATA_PLAYER_BED_POSITION' => 28,
		'DATA_LEAD_HOLDER' => 37,
		'DATA_SCALE' => 38,
		'DATA_BUTTON_TEXT' => 99,
		'DATA_MAX_AIR' => 42,
		'DATA_WIDTH' => 53,
		'DATA_HEIGHT' => 54,
		'DATA_EXPLODE_TIMER' => 55,
		'DATA_SEAT_RIDER_OFFSET' => 56,
		'DATA_POSE_INDEX' => 78,
	];
	
	private static $diffEntityMetaIds354 = [
		'DATA_PLAYER_FLAGS' => 26,
		'DATA_PLAYER_BED_POSITION' => 28,
		'DATA_LEAD_HOLDER' => 37,
		'DATA_SCALE' => 38,
		'DATA_BUTTON_TEXT' => 99,
		'DATA_MAX_AIR' => 42,
		'DATA_WIDTH' => 54,
		'DATA_HEIGHT' => 55,
		'DATA_EXPLODE_TIMER' => 56,
		'DATA_SEAT_RIDER_OFFSET' => 57,
		'DATA_POSE_INDEX' => 79,
	];
	
	private static $diffEntityMetaIds360 = [
		'DATA_PLAYER_FLAGS' => 26,
		'DATA_PLAYER_BED_POSITION' => 28,
		'DATA_LEAD_HOLDER' => 37,
		'DATA_SCALE' => 38,
		'DATA_BUTTON_TEXT' => 99,
		'DATA_MAX_AIR' => 42,
		'DATA_WIDTH' => 53,
		'DATA_HEIGHT' => 54,
		'DATA_EXPLODE_TIMER' => 55,
		'DATA_SEAT_RIDER_OFFSET' => 56,
		'DATA_POSE_INDEX' => 78,
	];
	
	private static $entityMetaIds120 = [];
	private static $entityMetaIds220 = [];
	private static $entityMetaIds221 = [];
	private static $entityMetaIds340 = [];
	private static $entityMetaIds354 = [];
	private static $entityMetaIds360 = [];

	public static function init() {
		$oClass = new \ReflectionClass('pocketmine\entity\Entity');
		self::$initialMeta = $oClass->getConstants();

		foreach (self::$diffEntityFlags120 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityFlags120[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityFlags221 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityFlags221[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityFlags290 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityFlags290[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds120 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds120[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds220 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds220[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds221 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds221[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds340 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds340[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds354 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds354[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds360 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds360[self::$initialMeta[$key]] = $value;
			}
		}
	}

	public static function updateMeta($meta, $protocol) {
		$meta = self::updateEntityFlags($meta, $protocol);
		$meta = self::updateMetaIds($meta, $protocol);
		return $meta;
	}

	private static function updateMetaIds($meta, $protocol) {
		switch ($protocol) {
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_385:
			case Info::PROTOCOL_370:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_360:
				$protocolMeta = self::$entityMetaIds360;
				break;
			case Info::PROTOCOL_354:
				$protocolMeta = self::$entityMetaIds354;
				break;
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_340:
				$protocolMeta = self::$entityMetaIds340;
				break;
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_330:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_290:
			case Info::PROTOCOL_282:
			case Info::PROTOCOL_280:
			case Info::PROTOCOL_274:
			case Info::PROTOCOL_273:
			case Info::PROTOCOL_271:
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_221:
				$protocolMeta = self::$entityMetaIds221;
				break;
			case Info::PROTOCOL_220:
				$protocolMeta = self::$entityMetaIds220;
				break;
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:			
				$protocolMeta = self::$entityMetaIds120;
				break;
			default:
				return $meta;
		}
		$newMeta = [];
		foreach ($meta as $key => $value) {
			if (isset($protocolMeta[$key])) {
				$newMeta[$protocolMeta[$key]] = $value;
			} else {
				$newMeta[$key] = $value;
			}
		}
		return $newMeta;
	}

	private static function updateEntityFlags($meta, $protocol) {
		if (!isset($meta[Entity::DATA_FLAGS])) {
			return $meta;
		}
		switch ($protocol) {
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_385:
			case Info::PROTOCOL_370:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_360:
			case Info::PROTOCOL_354:
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_340:
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_330:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_290:
				$newflags = 1 << 19; //DATA_FLAG_CAN_CLIMBING
				$protocolFlags = self::$entityFlags290;
				break;
			case Info::PROTOCOL_282:
			case Info::PROTOCOL_280:
			case Info::PROTOCOL_274:
			case Info::PROTOCOL_273:
			case Info::PROTOCOL_271:
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_221:
				$newflags = 1 << 19; //DATA_FLAG_CAN_CLIMBING
				$protocolFlags = self::$entityFlags221;
				break;
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_220:
				$newflags = 1 << 19; //DATA_FLAG_CAN_CLIMBING
				$protocolFlags = self::$entityFlags120;
				break;
			default:
				return $meta;
		}
		
		$flags = strrev(decbin($meta[Entity::DATA_FLAGS][1]));
		$flagsLength = strlen($flags);
		for ($i = 0; $i < $flagsLength; $i++) {
			if ($flags{$i} === '1') {
				$newflags |= 1 << (isset($protocolFlags[$i]) ? $protocolFlags[$i] : $i);
			}
		}
		$meta[Entity::DATA_FLAGS][1] = $newflags;
		return $meta;
	}

}

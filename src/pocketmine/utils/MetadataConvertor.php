<?php

namespace pocketmine\utils;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\Info;

class MetadataConvertor {

	private static $initialMeta = [];
	private static $diffEntityFlags110 = [
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
	private static $entityFlags110 = [];
	private static $diffEntityMetaIds110 = [
		'DATA_MAX_AIR' => 43,
	];
	private static $diffEntityMetaIds120 = [
		'DATA_AIR' => 12,
		'DATA_SCALE' => 44,
		'DATA_MAX_AIR' => 48,
	];
	private static $entityMetaIds110 = [];
	private static $entityMetaIds120 = [];

	public static function init() {
		$oClass = new \ReflectionClass('pocketmine\entity\Entity');
		self::$initialMeta = $oClass->getConstants();

		foreach (self::$diffEntityFlags110 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityFlags110[self::$initialMeta[$key]] = $value;
			}
		}

		foreach (self::$diffEntityMetaIds110 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds110[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds110 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds110[self::$initialMeta[$key]] = $value;
			}
		}
		
		foreach (self::$diffEntityMetaIds120 as $key => $value) {
			if (isset(self::$initialMeta[$key])) {
				self::$entityMetaIds120[self::$initialMeta[$key]] = $value;
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
			case Info::PROTOCOL_120:
				$protocolMeta = self::$entityMetaIds120;
				break;
			case Info::PROTOCOL_110:
				$protocolMeta = self::$entityMetaIds110;
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
		switch ($protocol) {
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_110:
				if (isset($meta[Entity::DATA_FLAGS])) {
					$newflags = 1 << 19; //DATA_FLAG_CAN_CLIMBING
					$flags = strrev(decbin($meta[Entity::DATA_FLAGS][1]));
					for ($i = 0; $i < strlen($flags); $i++) {
						if ($flags{$i} === '1') {
							$newflags |= 1 << (isset(self::$entityFlags110[$i]) ? self::$entityFlags110[$i] : $i);
						}
					}
					$meta[Entity::DATA_FLAGS][1] = $newflags;
				}
				return $meta;
			default:
				return $meta;
		}
	}

}

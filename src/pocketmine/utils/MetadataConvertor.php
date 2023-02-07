<?php

namespace pocketmine\utils;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\Info;

class MetadataConvertor {

	private static $initialMeta = [];

	private const diffEntityFlags560 = [
		'DATA_FLAG_HAS_COLLISION' => 48,
		'DATA_FLAG_AFFECTED_BY_GRAVITY' => 49,
		'DATA_FLAG_FIRE_IMMUNE' => 50,
	];

	private static array $entityFlags560 = [];

	public static function init() {
		$oClass = new \ReflectionClass('pocketmine\entity\Entity');
		self::$initialMeta = $oClass->getConstants();

		foreach(self::$initialMeta as $name => $value){
			if(isset(self::diffEntityFlags560[$name])){
				self::$entityFlags560[$value] = self::diffEntityFlags560[$name];
			}else{
				self::$entityFlags560[$value] = $value;
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
			case Info::PROTOCOL_567:
			case Info::PROTOCOL_560:
			case Info::PROTOCOL_557:
			case Info::PROTOCOL_554:
			case Info::PROTOCOL_553:
			case Info::PROTOCOL_545:
			case Info::PROTOCOL_544:
			case Info::PROTOCOL_534:
			case Info::PROTOCOL_527:
			case Info::PROTOCOL_526:
			case Info::PROTOCOL_503:
			case Info::PROTOCOL_486:
			case Info::PROTOCOL_475:
			case Info::PROTOCOL_471:
			case Info::PROTOCOL_465:
			case Info::PROTOCOL_448:
			case Info::PROTOCOL_440:
			case Info::PROTOCOL_431:
            case Info::PROTOCOL_428:
            case Info::PROTOCOL_423:
            case Info::PROTOCOL_422:
            case Info::PROTOCOL_419:
				$protocolMeta = [];
				break;
			default:
				throw new \InvalidArgumentException("Unknown protocol $protocol");
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
			case Info::PROTOCOL_567:
			case Info::PROTOCOL_560:
				$newflags = 0;
				$changedFlagIds = self::$entityFlags560;
				break;
			case Info::PROTOCOL_557:
			case Info::PROTOCOL_554:
			case Info::PROTOCOL_553:
			case Info::PROTOCOL_545:
			case Info::PROTOCOL_544:
			case Info::PROTOCOL_534:
			case Info::PROTOCOL_527:
			case Info::PROTOCOL_526:
			case Info::PROTOCOL_503:
			case Info::PROTOCOL_486:
			case Info::PROTOCOL_475:
			case Info::PROTOCOL_471:
			case Info::PROTOCOL_465:
			case Info::PROTOCOL_448:
			case Info::PROTOCOL_440:
			case Info::PROTOCOL_431:
            case Info::PROTOCOL_428:
            case Info::PROTOCOL_423:
            case Info::PROTOCOL_422:
            case Info::PROTOCOL_419:
				$changedFlagIds = [];
				$newflags = 0;
				break;
			default:
				throw new \InvalidArgumentCountException("Unknown protocol $protocol");
		}

		$flags = strrev(decbin($meta[Entity::DATA_FLAGS][1]));
		$flagsLength = strlen($flags);
		for ($i = 0; $i < $flagsLength; $i++) {
			if ($flags[$i] === '1') {
				$newflags |= 1 << (isset($changedFlagIds[$i]) ? $changedFlagIds[$i] : $i);
			}
		}
		$meta[Entity::DATA_FLAGS][1] = $newflags;
		return $meta;
	}

}

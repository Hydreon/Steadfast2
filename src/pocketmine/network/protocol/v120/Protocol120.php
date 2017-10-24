<?php

namespace pocketmine\network\protocol\v120;

abstract class Protocol120 {

	const CONTAINER_ID_NONE = -1;
	const CONTAINER_ID_INVENTORY = 0;
	const CONTAINER_ID_FIRST = 1;
	const CONTAINER_ID_LAST = 100;
	const CONTAINER_ID_OFFHAND = 119;
	const CONTAINER_ID_ARMOR = 120;
	const CONTAINER_ID_CREATIVE = 121;
	const CONTAINER_ID_SELECTION_SLOTS = 122;
	const CONTAINER_ID_FIXEDINVENTORY = 123;
	const CONTAINER_ID_CURSOR_SELECTED = 124;
	
	private static $disallowedPackets = [
		'pocketmine\network\protocol\AddItemPacket',
		'pocketmine\network\protocol\ContainerSetContentPacket',
		'pocketmine\network\protocol\ContainerSetSlotPacket',
		'pocketmine\network\protocol\DropItemPacket',
		'pocketmine\network\protocol\InventoryActionPacket',
		'pocketmine\network\protocol\ReplaceSelectedItemPacket',
		'pocketmine\network\protocol\RemoveBlockPacket',
		'pocketmine\network\protocol\UseItemPacket',
	];
	
	public static function getDisallowedPackets() {
		return self::$disallowedPackets;
	}
	
}

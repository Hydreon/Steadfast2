<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\Info;

class MapItemDataPacket extends PEPacket {

	const NETWORK_ID = Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET;
	const PACKET_NAME = "CLIENTBOUND_MAP_ITEM_DATA_PACKET";
	
	const TRACKED_OBJECT_TYPE_ENTITY = 0;
	const TRACKED_OBJECT_TYPE_BLOCK = 1;

	public $mapId;
	public $flags;
	public $scale;
	public $width;
	public $height;
	public $data;
	public $pointners = [];
	public $entityIds = [];
	public $isLockedMap = false;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->mapId);
		$this->putVarInt($this->flags);
		$this->putByte(0); // dimension
		if ($playerProtocol >= Info::PROTOCOL_351) {
			$this->putByte($this->isLockedMap);
		}
		switch ($this->flags) {
			case 2:
				$this->putByte($this->scale);
				$this->putSignedVarInt($this->width);
				$this->putSignedVarInt($this->height);
				$this->putSignedVarInt(0);
				$this->putSignedVarInt(0);
				$this->putVarInt($this->width * $this->height);
				$this->put($this->data);
				break;
			case 4:
				$this->putByte($this->scale);
				if (($entityCount = count($this->entityIds)) < ($pointnerCount = count($this->pointners))) {
					$lastFaKeId = -1;
					while ($entityCount < $pointnerCount) {
						array_unshift($this->entityIds, $lastFaKeId--);
						$entityCount++;
					}
				}
				$this->putVarInt($entityCount);
				foreach ($this->entityIds as $entityId) {
					if ($playerProtocol >= Info::PROTOCOL_271) {
						$this->putLInt(self::TRACKED_OBJECT_TYPE_ENTITY);
					}
					$this->putSignedVarInt($entityId);
				}
				$this->putVarInt(count($this->pointners));
				foreach ($this->pointners as $pointner) {
					$this->putByte($pointner['type']);
					$this->putByte($pointner['rotate']);					
					if ($pointner['x'] > 0x7f) {
						$pointner['x'] = 0x7f;
					}
					if ($pointner['x'] < -0x7f) {
						$pointner['x'] = -0x7f;
					}
					if ($pointner['z'] > 0x7f) {
						$pointner['z'] = 0x7f;
					}
					if ($pointner['z'] < -0x7f) {
						$pointner['z'] = -0x7f;
					}
					$this->putByte($pointner['x']);
					$this->putByte($pointner['z']);
					$this->putString('');
					$this->putVarInt(hexdec($pointner['color']));
				}
				break;
		}
	}

}

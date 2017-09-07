<?php

namespace pocketmine\network\protocol;

use pocketmine\network\protocol\Info;

class MapItemDataPacket extends PEPacket {

	const NETWORK_ID = Info::CLIENTBOUND_MAP_ITEM_DATA_PACKET;
	const PACKET_NAME = "CLIENTBOUND_MAP_ITEM_DATA_PACKET";

	public $mapId;
	public $flags;
	public $scale;
	public $width;
	public $height;
	public $data;
	public $pointners = [];
	public $entityIds = [];

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->mapId);
		$this->putVarInt($this->flags);
		if ($playerProtocol >= Info::PROTOCOL_120) {
			$this->putByte(0); // dimension
		}
		switch ($this->flags) {
			case 2:
				$this->putByte($this->scale);
				$this->putSignedVarInt($this->width);
				$this->putSignedVarInt($this->height);
				$this->putSignedVarInt(0);
				$this->putSignedVarInt(0);
				if ($playerProtocol >= Info::PROTOCOL_120) {
					$this->putVarInt($this->width * $this->height);
				}
				$this->put($this->data);
				break;
			case 4:
				$this->putByte($this->scale);
				if ($playerProtocol >= Info::PROTOCOL_120) {
					if (!empty($this->entityIds)) {
						$this->putVarInt(count($this->entityIds));
						foreach ($this->entityIds as $entityId) {
							$this->putSignedVarInt($entityId);
						}
					} else {
						$this->put("\x01\xfd\xff\xff\xff\x1f"); // hack for 1.2, crash if send 0 as entity count
					}
				}
				$this->putVarInt(count($this->pointners));
				foreach ($this->pointners as $pointner) {
					if ($playerProtocol >= Info::PROTOCOL_120) {
						$this->putByte($pointner['type']);
						$this->putByte($pointner['rotate']);
					} else {
						$this->putSignedVarInt($pointner['type'] << 4 | $pointner['rotate']);
					}
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
					if ($playerProtocol >= Info::PROTOCOL_120) {
						$this->putVarInt(hexdec($pointner['color']));
					} else {
						$this->putLInt(hexdec($pointner['color']));
					}
				}
				break;
		}
	}

}

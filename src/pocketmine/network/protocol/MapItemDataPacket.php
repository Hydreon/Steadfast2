<?php

namespace pocketmine\network\protocol;

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

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putSignedVarInt($this->mapId);
		$this->putVarInt($this->flags);
		switch ($this->flags) {
			case 2:
				$this->putByte($this->scale);
				$this->putSignedVarInt($this->width);
				$this->putSignedVarInt($this->height);
				$this->putSignedVarInt(0);
				$this->putSignedVarInt(0);
				$this->put($this->data);
				break;
			case 4:
				$this->putByte($this->scale);
				$this->putVarInt(count($this->pointners));
				foreach ($this->pointners as $pointner) {
					$this->putSignedVarInt($pointner['type']);
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
					$this->put(hex2bin($pointner['color']));
				}
				break;
		}
	}

}

<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info331;
use pocketmine\network\protocol\PEPacket;

class SetScoreboardIdentityPacket extends PEPacket {

	const NETWORK_ID = Info331::SET_SCOREBOARD_IDENTITY_PACKET;
	const PACKET_NAME = "SET_SCOREBOARD_IDENTITY_PACKET";
	const TYPE_UPDATE_IDENTITY = 0;
	const TYPE_REMOVE_IDENTITY = 1;

	public $entries = [];
	public $type;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->type);
		$this->putVarInt(count($this->entries));
		foreach ($this->entries as $entry) {
			$this->putSignedVarInt($entry['scoreboardId']);
			if ($this->type === self::TYPE_UPDATE_IDENTITY) {
				$this->putEntityUniqueId($entry['id']);
			}
		}
	}

	public function decode($playerProtocol) {
		
	}

}

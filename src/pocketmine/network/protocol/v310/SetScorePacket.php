<?php

namespace pocketmine\network\protocol\v310;

use pocketmine\network\protocol\Info310;
use pocketmine\network\protocol\PEPacket;

class SetScorePacket extends PEPacket {

	const NETWORK_ID = Info310::SET_SCORE_PACKET;
	const PACKET_NAME = "SET_SCORE_PACKET";
	const TYPE_CHANGE = 0;
	const TYPE_REMOVE = 1;
	const ENTRY_TYPE_PLAYER = 1;
	const ENTRY_TYPE_ENTITY = 2;
	const ENTRY_TYPE_FAKE_PLAYER = 3;

	public $entries = [];
	public $type;

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putByte($this->type);
		$this->putVarInt(count($this->entries));
		foreach ($this->entries as $entry) {
			$this->putSignedVarInt($entry['scoreboardId']);
			$this->putString($entry['objectiveName']);
			$this->putLInt($entry['score']);
			if ($this->type !== self::TYPE_REMOVE) {
				$this->putByte($entry['type']);
				switch ($entry['type']) {
					case self::ENTRY_TYPE_PLAYER:
					case self::ENTRY_TYPE_ENTITY:
						$this->putVarInt($entry['id']);
						break;
					case self::ENTRY_TYPE_FAKE_PLAYER:
						$this->putString($entry['customName']);
						break;
				}
			}
		}
	}

	public function decode($playerProtocol) {
		
	}

}

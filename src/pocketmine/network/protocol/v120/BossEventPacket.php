<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class BossEventPacket extends PEPacket {
	
	const NETWORK_ID = Info120::BOSS_EVENT_PACKET;
	const PACKET_NAME = "BOSS_EVENT_PACKET";
	
	const EVENT_TYPE_ADD = 0; // from server to client only
	const EVENT_TYPE_PLAYER_ADDED = 1; // from client to server only
	const EVENT_TYPE_REMOVE = 2;
	const EVENT_TYPE_PLAYER_REMOVED = 3;
	const EVENT_TYPE_UPDATE_PERCENT = 4; // from server to client only
	const EVENT_TYPE_UPDATE_NAME = 5;
	const EVENT_TYPE_UPDATE_PROPERTIES = 6;
	const EVENT_TYPE_UPDATE_STYLE = 7;
	
	public $eid = -1;
	public $eventType = self::EVENT_TYPE_ADD;
	public $bossName = "";
	public $darkenScreen = 12;
	public $color = 5;
	public $overlay = 0;
	public $playerID = -1;
	public $healthPercent = 1.0; // from 0 to 1
	
	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		$this->reset($playerProtocol);
		$this->putVarInt($this->eid);
		$this->putVarInt($this->eventType);
		switch ($this->eventType) {
			case self::EVENT_TYPE_ADD:
				$this->putString($this->bossName);
				$this->putLFloat($this->healthPercent);
				$this->putLShort($this->darkenScreen);
				$this->putVarInt($this->color);
				$this->putVarInt($this->overlay);
				break;
			case self::EVENT_TYPE_PLAYER_ADDED:
			case self::EVENT_TYPE_PLAYER_REMOVED:
				$this->putVarInt($this->playerID);
				break;
			case self::EVENT_TYPE_UPDATE_PERCENT:
				$this->putLFloat($this->healthPercent);
				break;
			case self::EVENT_TYPE_UPDATE_NAME:
				$this->putString($this->bossName);
				break;
			case self::EVENT_TYPE_UPDATE_PROPERTIES:
				$this->putLShort($this->darkenScreen);
				$this->putVarInt($this->color);
				$this->putVarInt($this->overlay);
				break;
			case self::EVENT_TYPE_UPDATE_STYLE:
				$this->putVarInt($this->color);
				$this->putVarInt($this->overlay);
				break;
		}
	}

}

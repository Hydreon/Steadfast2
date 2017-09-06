<?php

namespace pocketmine\network\protocol\v120;

use pocketmine\network\protocol\Info120;
use pocketmine\network\protocol\PEPacket;

class CommandRequestPacket extends PEPacket {
	
	const NETWORK_ID = Info120::COMMAND_REQUEST_PACKET;
	const PACKET_NAME = "COMMAND_REQUEST_PACKET";
	
	const TYPE_PLAYER = 0;
	const TYPE_COMMAND_BLOCK = 1;
	const TYPE_MINECART_COMMAND_BLOCK = 2;
	const TYPE_DEV_CONSOLE = 3;
	const TYPE_AUTOMATION_PLAYER = 4;
	const TYPE_CLIENT_AUTOMATION = 5;
	const TYPE_DEDICATED_SERVER = 6;
	const TYPE_ENTITY = 7;
	const TYPE_VIRTUAL = 8;
	const TYPE_GAME_ARGUMENT = 9;
	const TYPE_INTERNAL = 10;
	
	/** @var string */
	public $command = '';
	/** @var unsigned integer */
	public $commandType = self::TYPE_PLAYER;
	/** @var string */
	public $requestId = '';
	/** @var integer */
	public $playerId = '';
	
	public function decode($playerProtocol) {
		$this->getHeader($playerProtocol);
		$this->command = $this->getString();
		$this->commandType = $this->getVarInt();
		$this->requestId = $this->getString();
		if ($this->commandType == self::TYPE_DEV_CONSOLE) {
			$this->playerId = $this->getSignedVarInt();
		}
	}

	public function encode($playerProtocol) {
	}
	
}

<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\network;

use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\UnknownPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\TextFormat;
use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use raklib\server\RakLibServer;
use raklib\server\ServerHandler;
use raklib\server\ServerInstance;
use pocketmine\utils\Binary;
use pocketmine\utils\BinaryStream;
use pocketmine\network\protocol\BatchPacket;

class RakLibInterface implements ServerInstance, AdvancedSourceInterface{
	
	/** @var Server */
	private $server;

	/** @var Network */
	private $network;

	/** @var RakLibServer */
	private $rakLib;

	/** @var Player[] */
	private $players = [];

	/** @var \SplObjectStorage */
	private $identifiers;

	/** @var int[] */
	private $identifiersACK = [];

	/** @var ServerHandler */
	private $interface;

	public $count = 0;
	public $maxcount = 31360;
	public $name = "";

	public function setCount($count, $maxcount) {
		$this->count = $count;
		$this->maxcount = $maxcount;

		$this->interface->sendOption("name",
		"MCPE;".addcslashes($this->name, ";") .";".
		(Info::CURRENT_PROTOCOL).";".
//		\pocketmine\MINECRAFT_VERSION_NETWORK.";".
		''.";".
		$this->count.";".$maxcount . ";". Server::getServerId()
		);
	}

	public function __construct(Server $server){

		$this->server = $server;
		$this->identifiers = new \SplObjectStorage();

		$this->rakLib = new RakLibServer($this->server->getLogger(), $this->server->getLoader(), $this->server->getPort(), $this->server->getIp() === "" ? "0.0.0.0" : $this->server->getIp());
		$this->interface = new ServerHandler($this->rakLib, $this);

		for($i = 0; $i < 256; ++$i){
			$this->channelCounts[$i] = 0;
		}

		$this->setCount(count($this->server->getOnlinePlayers()), $this->server->getMaxPlayers());		
	}

	public function setNetwork(Network $network){
		$this->network = $network;
	}

	public function getUploadUsage() {
		return $this->network->getUpload();
	}

	public function getDownloadUsage() {
		return $this->network->getDownload();
	}

	public function doTick(){
		if(!$this->rakLib->isTerminated()){
			$this->interface->sendTick();
		}else{
			$info = $this->rakLib->getTerminationInfo();
			$this->network->unregisterInterface($this);
			\ExceptionHandler::handler(E_ERROR, "RakLib Thread crashed [".$info["scope"]."]: " . (isset($info["message"]) ? $info["message"] : ""), $info["file"], $info["line"]);
		}
	}

	public function process() {
		$max = $this->interface->getPacketQueueSize();
		while ($max && $this->interface->handlePacket()) {
			$max--;
		}
		if ($this->rakLib->isTerminated()) {
			$this->network->unregisterInterface($this);
			throw new \Exception("RakLib Thread crashed");
		}
	}

	public function closeSession($identifier, $reason){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$this->identifiers->detach($player);
			unset($this->players[$identifier]);
			unset($this->identifiersACK[$identifier]);
			if(!$player->closed){
				$player->close($player->getLeaveMessage(), $reason);
			}
		}
	}

	public function close(Player $player, $reason = "unknown reason"){
		if(isset($this->identifiers[$player])){
			unset($this->players[$this->identifiers[$player]]);
			unset($this->identifiersACK[$this->identifiers[$player]]);
			$this->interface->closeSession($this->identifiers[$player], $reason);
			$this->identifiers->detach($player);
		}
	}

	public function shutdown(){
		$this->interface->shutdown();
	}

	public function emergencyShutdown(){
		$this->interface->emergencyShutdown();
	}

	public function openSession($identifier, $address, $port, $clientID){
		$ev = new PlayerCreationEvent($this, Player::class, Player::class, null, $address, $port);
		$this->server->getPluginManager()->callEvent($ev);
		$class = $ev->getPlayerClass();

		$player = new $class($this, $ev->getClientId(), $ev->getAddress(), $ev->getPort());
		$this->players[$identifier] = $player;
		$this->identifiersACK[$identifier] = 0;
		$this->identifiers->attach($player, $identifier);
		$player->setIdentifier($identifier);
		$this->server->addPlayer($identifier, $player);
	}

	public function handleEncapsulated($identifier, $buffer){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			try{
				if($buffer !== ""){
					$pk = $this->getPacket($buffer, $player);			
					if (!is_null($pk)) {
						try {
							$pk->decode($player->getPlayerProtocol());
						}catch(\Exception $e){
							file_put_contents("logs/" . date('Y.m.d') . "_decode_error.log", $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
							return;
						}
						$player->handleDataPacket($pk);
					}
				}
			}catch(\Exception $e){
				error_log($e->getMessage());
			}
		}
	}
	
	public function handlePing($identifier, $ping){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$player->setPing($ping);
		}
	}
	
	public function handleKick($identifier, $reason){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$player->kick($reason);
		}
	}

	public function blockAddress($address, $timeout = 300){
		$this->interface->blockAddress($address, $timeout);
	}

	public function handleRaw($address, $port, $payload){
		$this->server->handlePacket($address, $port, $payload);
	}

	public function sendRawPacket($address, $port, $payload){
		$this->interface->sendRaw($address, $port, $payload);
	}

	public function setName($name){
		if(strlen($name) > 1) {
			$this->name = $name;
		}
	}

	public function setPortCheck($name){
		$this->interface->sendOption("portChecking", (bool) $name);
	}

	public function handleOption($name, $value){
		if($name === "bandwidth"){
			$v = unserialize($value);
			$this->network->addStatistics($v["up"], $v["down"]);
		}
	}

	private function getPacket($buffer, $player){	
		$tmpStream = new BinaryStream($buffer);
		$header = $tmpStream->getVarInt();
		$pid = $header & 0x3FF;		
		if ($pid == 0x13) { //speed hack
			$player->setLastMovePacket($buffer);
			return null;
		}
		if (($data = $this->network->getPacket($pid, $player->getPlayerProtocol())) === null) {
			return null;
		}
		$data->setBuffer($buffer);
		return $data;
	}

    public function putReadyPacket(Player $player, $packet) {
        if (isset($this->identifiers[$player])) {
            $pk = new CachedEncapsulatedPacket();
            $pk->buffer = $packet;
            $pk->reliability = 3;
            $this->interface->sendEncapsulated($player->getIdentifier(), $pk, RakLib::PRIORITY_NORMAL);
            return $pk->identifierACK;
        }
    }
	
	public function putPacket($player, $buffer) {
		if (isset($this->identifiers[$player])) {
			$pk = new EncapsulatedPacket();
			$pk->buffer = $buffer;
			$pk->reliability = 3;
			$this->interface->sendEncapsulated($player->getIdentifier(), $pk,  RakLib::PRIORITY_NORMAL | RakLib::FLAG_NEED_ZLIB);
		}
	}

    public function newputPacket(Player $player, DataPacket $packet){
        if (isset($this->identifiers[$player])) {
            if(!$packet->isEncoded){
                $packet->encode($player->protocol);
            }
            $this->server->batchPacket([$player], [$packet]);
            return null;
        }
        return null;
    }
	
	public function enableEncryptForPlayer(Player $player, $token, $privateKey, $publicKey){
		$identifier = $this->identifiers[$player];	
		$this->interface->enableEncrypt($identifier, $token, $privateKey, $publicKey);
	}
	
	public function getRakLib() {
		return $this->rakLib;
	}

}

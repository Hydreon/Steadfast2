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
use pocketmine\network\protocol\BatchPacket;
use pocketmine\utils\Binary;

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
		\pocketmine\MINECRAFT_VERSION_NETWORK.";".
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

	public function process(){
		$work = false;
		if($this->interface->handlePacket()){
			$work = true;
			while($this->interface->handlePacket()){
			}
		}

		if($this->rakLib->isTerminated()){
			$this->network->unregisterInterface($this);

			throw new \Exception("RakLib Thread crashed");
		}

		return $work;
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
				var_dump($e->getMessage());
				$this->interface->blockAddress($player->getAddress(), 5);
			}
		}
	}
	
	public function handlePing($identifier, $ping){
		if(isset($this->players[$identifier])){
			$player = $this->players[$identifier];
			$player->setPing($ping);
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

	public function notifyACK($identifier, $identifierACK){
		if(isset($this->players[$identifier])){
			$this->players[$identifier]->handleACK($identifierACK);
		}
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

	/*
	 * $player - packet recipient
	 */
	public function putPacket(Player $player, DataPacket $packet, $needACK = false, $immediate = false){
		if(isset($this->identifiers[$player])){			
			$protocol = $player->getPlayerProtocol();
			$packet->encode($protocol);
//			var_dump("Send: 0x" . ($packet::NETWORK_ID < 16 ? '0' . dechex($packet::NETWORK_ID) : dechex($packet::NETWORK_ID)));
			if(!($packet instanceof BatchPacket) && strlen($packet->buffer) >= Network::$BATCH_THRESHOLD){
				$this->server->batchPackets([$player], [$packet], true);
				return null;
			}
			$identifier = $this->identifiers[$player];	

			$pk = new EncapsulatedPacket();				
			$pk->buffer = chr(0xfe) . $this->getPacketBuffer($packet, $protocol);
			$pk->reliability = 3;

			if($needACK === true){
				$pk->identifierACK = $this->identifiersACK[$identifier]++;
			}
			
			if($player->isEncryptEnable()) {
				$pk->buffer = chr(0xfe) . $player->getEncrypt(substr($pk->buffer,1));
			}

			if ($immediate) {
				$pk->reliability = 0;
			}

			$this->interface->sendEncapsulated($identifier, $pk, ($needACK === true ? RakLib::FLAG_NEED_ACK : 0) | ($immediate === true ? RakLib::PRIORITY_IMMEDIATE : RakLib::PRIORITY_NORMAL));
		}

		return null;
	}
	

	private function getPacket($buffer, $player){
		$playerProtocol = $player->getPlayerProtocol();
		if ($player->isEncryptEnable()) {
			$buffer = $player->getDecrypt($buffer);			
		}		
		if ($playerProtocol >= Info::PROTOCOL_110 || $player->getOriginalProtocol() == 0 && $this->isZlib($buffer)) {
			$pk = new BatchPacket($buffer);
			$pk->is110 = true;
			return $pk;
		}
		$pid = ord($buffer{0});
		if (($data = $this->network->getPacket($pid, $playerProtocol)) === null) {
			return null;
		}
		$offset = 1;
		$data->setBuffer($buffer, $offset);
		return $data;
	}

	public function putReadyPacket($player, $buffer) {
		if (isset($this->identifiers[$player])) {	
			$pk = new EncapsulatedPacket();
			$pk->buffer = chr(0xfe) . $buffer;
			$pk->reliability = 3;	
			if($player->isEncryptEnable()) {
				$pk->buffer = chr(0xfe) . $player->getEncrypt(substr($pk->buffer,1));
			}
			$this->interface->sendEncapsulated($player->getIdentifier(), $pk, RakLib::PRIORITY_NORMAL);			
		}
	}
	
	private function getPacketBuffer($packet, $protocol) {
		if ($protocol < Info::PROTOCOL_110 || ($packet instanceof BatchPacket)) {
			return $packet->buffer;
		}
		
		return $this->fakeZlib(Binary::writeVarInt(strlen($packet->buffer)) . $packet->buffer);
	}
	
	private function fakeZlib($buffer) {
		static $startBytes = "\x78\x01\x01";
		$len = strlen($buffer);
		return $startBytes . Binary::writeLShort($len) . Binary::writeLShort($len ^ 0xffff) . $buffer . hex2bin(hash('adler32', $buffer, false));
	}
	
	private function isZlib($buffer) {
		if (ord($buffer{0}) == 120) {
			return true;
		}
		return false;
	}

}

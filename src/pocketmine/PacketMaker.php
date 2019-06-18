<?php

namespace pocketmine;

use raklib\protocol\EncapsulatedPacket;
use raklib\RakLib;
use pocketmine\network\protocol\DataPacket;
use pocketmine\utils\Binary;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\proxylib\ProxyServer;
use pocketmine\network\ProxyInterface;

class PacketMaker extends Thread {

	protected $classLoader;
	protected $shutdown;
	protected $internalQueue;
	protected $raklib;
	protected $proxy;

	public function __construct(\ClassLoader $loader, $raklib, $proxy) {
		$this->internalQueue = new \Threaded;
		$this->shutdown = false;
		$this->classLoader = $loader;
		$this->raklib = $raklib;
		$this->proxy = $proxy;
		$this->start(PTHREADS_INHERIT_CONSTANTS);
	}

	public function join() {
		$this->shutdown = true;
		parent::join();
	}

	public function pushMainToThreadPacket($data) {
		$this->internalQueue[] = $data;
	}

	public function readMainToThreadPacket() {
		return $this->internalQueue->shift();
	}

	public function run() {
		$this->registerClassLoader();
		gc_enable();
		ini_set("memory_limit", -1);
		ini_set("display_errors", 1);
		ini_set("display_startup_errors", 1);

		set_error_handler([$this, "errorHandler"], E_ALL);
		DataPacket::initPackets();
		$this->tickProcessor();
	}

	protected function tickProcessor() {
		while (!$this->shutdown) {
			$start = microtime(true);
			$this->tick();
			$time = microtime(true) - $start;
			if ($time < 0.024) {
				@time_sleep_until(microtime(true) + 0.025 - $time);
			}
		}
	}

	protected function tick() {
		while (count($this->internalQueue) > 0) {
			$data = unserialize($this->readMainToThreadPacket());
			$this->checkPacket($data);
		}
	}

	protected function checkPacket($data) {
		$moveData = $data['data'];
		$playersData = $data['player'];
		$encodedPackets = [];
		foreach ($playersData as $identifier => $playerData) {	
			$moveStr = '';
			foreach ($playerData['subIds'] as $subClientId => $entityIds) {
				$playerIndex = ($playerData['playerProtocol'] << 4) | $subClientId;
				foreach ($entityIds as $eid) {
					if (!isset($encodedPackets[$eid][$playerIndex])) {
						if (!isset($moveData[$eid])) {
							continue;
						}
						$singleMoveData = $moveData[$eid];
						if ($singleMoveData[7]) {
							$pk = new MovePlayerPacket();
							$pk->eid = $singleMoveData[0];
							$pk->x = $singleMoveData[1];
							$pk->y = $singleMoveData[2];
							$pk->z = $singleMoveData[3];
							$pk->pitch = $singleMoveData[6];
							$pk->yaw = $singleMoveData[5];
							$pk->bodyYaw = $singleMoveData[4];
						} else {
							$pk = new MoveEntityPacket();
							$pk->entities = [$singleMoveData];
						}
						$pk->senderSubClientID = $subClientId;
						$pk->encode($playerData['playerProtocol']);
						$buffer = $pk->getBuffer();
						$encodedPackets[$eid][$playerIndex] = Binary::writeVarInt(strlen($buffer)) . $buffer;
					}
					$moveStr .= $encodedPackets[$eid][$playerIndex];
				}
			}
			if (!empty($moveStr)) {
				$buffer = zlib_encode($moveStr, ZLIB_ENCODING_DEFLATE, 7);
				$this->sendData($identifier, $buffer, $playerData);
			}
		}
	}

	protected function sendData($identifier, $buffer, $data) {
		if (!is_null($this->proxy) && !empty($data['proxySessionId']) && !empty($data['proxyId'])) {
			$infoData = pack('N', $data['proxySessionId']) . chr(ProxyInterface::STANDART_PACKET_ID) . $buffer;
			$info = chr(strlen($data['proxyId'])) . $data['proxyId'] . $infoData;
			$this->proxy->writeToProxyServer($info);
		} elseif(!is_null($this->raklib)) {
			$pk = new EncapsulatedPacket();
			$pk->buffer = $buffer;
			$pk->reliability = 3;
			$enBuffer = chr(RakLib::PACKET_ENCAPSULATED) . chr(strlen($identifier)) . $identifier . chr(RakLib::PRIORITY_NORMAL) . $pk->toBinary(true);
			$this->raklib->pushMainToThreadPacket($enBuffer);
		}
	}

}

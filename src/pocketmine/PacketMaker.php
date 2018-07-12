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

class PacketMaker extends Thread {

	protected $classLoader;
	protected $shutdown;
	protected $internalQueue;
	protected $raklib;

	public function __construct(\ClassLoader $loader, $raklib) {
		$this->internalQueue = new \Threaded;
		$this->shutdown = false;
		$this->classLoader = $loader;
		$this->raklib = $raklib;
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
		foreach ($data['moveData'] as $identifier => $moveData) {
			$moveStr = "";
			foreach ($moveData['data'] as $singleMoveData) {
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
				$pk->senderSubClientID = $singleMoveData[8];
				$pk->encode($moveData['playerProtocol']);
				$buffer = $pk->getBuffer();
				$moveStr .= Binary::writeVarInt(strlen($buffer)) . $buffer;
			}
			$buffer = zlib_encode($moveStr, ZLIB_ENCODING_DEFLATE, 7);
			$pkBatch = new BatchPacket();
			$pkBatch->payload = $buffer;
			$pkBatch->encode($moveData['playerProtocol']);
			$pkBatch->isEncoded = true;
			$this->sendData($identifier, $pkBatch->getBuffer());
		}
		foreach ($data['motionData'] as $identifier => $motionData) {
			$motionStr = "";
			foreach ($motionData['data'] as $singleMotionData) {
				$pk = new SetEntityMotionPacket();
				$pk->entities = [$singleMotionData];
				$pk->senderSubClientID = $singleMotionData[4];
				$pk->encode($motionData['playerProtocol']);
				$buffer = $pk->getBuffer();
				$motionStr .= Binary::writeVarInt(strlen($buffer)) . $buffer;
			}
			$buffer = zlib_encode($motionStr, ZLIB_ENCODING_DEFLATE, 7);
			$pkBatch = new BatchPacket();
			$pkBatch->payload = $buffer;
			$pkBatch->encode($motionData['playerProtocol']);
			$pkBatch->isEncoded = true;
			$this->sendData($identifier, $pkBatch->getBuffer());
		}
	}

	protected function sendData($identifier, $buffer) {
		$pk = new EncapsulatedPacket();
		$pk->buffer = $buffer;
		$pk->reliability = 3;
		$enBuffer = chr(RakLib::PACKET_ENCAPSULATED) . chr(strlen($identifier)) . $identifier . chr(RakLib::PRIORITY_NORMAL) . $pk->toBinary(true);
		$this->raklib->pushMainToThreadPacket($enBuffer);
	}

}

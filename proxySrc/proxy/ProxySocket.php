<?php

namespace proxy;

use proxy\Server;
use raklib\RakLib;
use raklib\Binary;

class ProxySocket {

	private $address;
	private $port;
	private $socket;
	private $lastMessage = '';
	private $server;
	private $connectTimeout = 5;
	private $waitPlayers = [];
	private $connectTime = 0;
	private $writeQueue = [];

	public function __construct($server, $address, $port, $wait = false) {
		$this->server = $server;
		$this->address = $address;
		$this->port = $port;
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_nonblock($this->socket);
		socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
		socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
		socket_set_option($this->socket, SOL_SOCKET, SO_LINGER, ["l_onoff" => 1, "l_linger" => 0]);
		$startTime = microtime(true);
		if ($wait) {
			while (!@socket_connect($this->socket, $address, $port)) {
				$err = socket_last_error($this->socket);
				$timeDiff = microtime(true) - $startTime;
				if (($err == 115 || $err == 114) && $timeDiff < $this->connectTimeout) {
					socket_clear_error($this->socket);
					continue;
				}
				if ($timeDiff < $this->connectTimeout) {
					$errno = socket_last_error();
					$error = socket_strerror($errno);
					throw new \Exception("Socket can't connect : {$errno} - {$error}");
				} else {
					throw new \Exception("Socket can't connect : timeout");
				}
			}
			socket_clear_error($this->socket);
		} else {
			@socket_connect($this->socket, $address, $port);
			$this->connectTime = microtime(true);
		}
	}

	public function getIdentifier() {
		return $this->address . $this->port;
	}

	public function writeMessage($msg) {
		if (strlen($msg) > 0) {
			$data = zlib_encode($msg, ZLIB_ENCODING_DEFLATE, 7);
			$this->writeQueue[] = pack('N',  strlen($data)) . $data;
		}
	}

	public function checkMessages() {
		$err = socket_last_error($this->socket);
		if ($err !== 0 && $err !== 35 && $err !== 11) {
			socket_close($this->socket);
			return false;
		}

		$this->checkReadQueue();
		$this->checkWriteQueue();

		return true;
	}

	private function checkWriteQueue() {
		foreach ($this->writeQueue as $key => $data) {
			$dataLength = strlen($data);
			socket_clear_error($this->socket);
			while (true) {
				$sentBytes = socket_write($this->socket, $data);
				if ($sentBytes === false) {
					$errno = socket_last_error($this->socket);
					echo 'PROXY SOCKET WRITE ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
					$this->writeQueue[$key] = $data;
					return;
				} else if ($sentBytes < $dataLength) {
					$data = substr($data, $sentBytes);
					$dataLength -= $sentBytes;
				} else {
					break;
				}
			}
			unset($this->writeQueue[$key]);
		}
	}

	
	
	private function checkReadQueue() {
		$data = $this->lastMessage;
		$atLeastOneRecived = false;
		while (strlen($buffer = socket_read($this->socket, 65535, PHP_BINARY_READ)) > 0) {
			$data .= $buffer;
			$atLeastOneRecived = true;
		}

		if (!$atLeastOneRecived) {
			$errno = socket_last_error($this->socket);
			if ($errno !== 11) {
				echo 'PROXY SOCKET READ ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
			}
			return;
		}
		
		$this->lastMessage = '';
		if (($dataLen = strlen($data)) > 0) {
			$offset = 0;
			while ($offset < $dataLen) {
				if ($offset + 4 > $dataLen) {
					$this->lastMessage = substr($data, $offset);
					break;
				}
				$len = unpack('N', substr($data, $offset, 4));
				$len = $len[1];
				if ($offset + $len + 4 > $dataLen) {
					$this->lastMessage = substr($data, $offset);
					break;
				}
				$offset += 4;
				$msg = substr($data, $offset, $len);
				$res = $this->checkPacket($msg);
				if (!$res) {
					var_dump('PROXY FATAL: Not zlib packet');
				}
				$offset += $len;
			}
		}
	}


	private function checkPacket($buffer) {
		$buffer = zlib_decode($buffer);
		if ($buffer === false) {
			return false;
		}
		$packetType = ord($buffer{0});
		$buffer = substr($buffer, 1);
		if ($packetType == Server::PLAYER_PACKET_ID) {
			$id = unpack('N', substr($buffer, 0, 4));
			$id = $id[1];
			$buffer = substr($buffer, 4);
			$type = ord($buffer{0});
			$buffer = substr($buffer, 1);
			$this->server->checkPacket($id, $buffer, $type);
		} else if ($packetType == Server::SYSTEM_PACKET_ID) {
			$offset = 0;
			$id = ord($buffer{$offset});
			$offset++;
			if ($id = RakLib::PACKET_RAW) {
				$len = ord($buffer{$offset++});
				$address = substr($buffer, $offset, $len);
				$offset += $len;
				$port = Binary::readShort(substr($buffer, $offset, 2));
				$offset += 2;
				$payload = substr($buffer, $offset);
				$this->server->saveRawPacket($address, $port, $payload);
			}
		} else if ($packetType == Server::SYSTEM_DATA_PACKET_ID) {
			$offset = 0;
			$id = ord($buffer{$offset});
			$offset++;
			if ($id == 0x02) {				
				$len = unpack('N', substr($buffer,$offset ,4))[1];

				$offset += 4;
				$this->server->raklibInterface->setFullName(substr($buffer,$offset, $len));
				$offset += $len;

				$len = unpack('N', substr($buffer,$offset ,4))[1];
				$offset += 4;
				$this->server->setLongData(substr($buffer,$offset, $len));
				$offset += $len;
				
				$len = unpack('N', substr($buffer,$offset ,4))[1];
				$offset += 4;
				$this->server->setShortData(substr($buffer,$offset, $len));
			}
		} else {
			echo 'UNKNOWN PACKET TYPE' . PHP_EOL;
			var_dump($buffer);
		}
		return true;
	}

	public function addWaitPlayer($player) {
		$this->waitPlayers[$player->proxyIdentifier] = $player;
	}

	public function checkConnect() {
		if (!@socket_connect($this->socket, $this->address, $this->port)) {
			$err = socket_last_error($this->socket);
			$timeDiff = microtime(true) - $this->connectTime;
			if (($err == 115 || $err == 114) && $timeDiff < $this->connectTimeout) {
				socket_clear_error($this->socket);
				return false;
			}
			if ($timeDiff < $this->connectTimeout) {
				$errno = socket_last_error();
				$error = socket_strerror($errno);
				throw new \Exception("Socket can't connect : {$errno} - {$error}");
			} else {
				throw new \Exception("Socket can't connect : timeout");
			}
		} else {
			foreach ($this->waitPlayers as $player) {
				$player->changeServer($this);
			}
			$this->waitPlayers = [];
			socket_clear_error($this->socket);
			return true;
		}
	}

}

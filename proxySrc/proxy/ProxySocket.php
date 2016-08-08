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

	public function __construct($server, $address, $port) {
		$this->server = $server;
		$this->address = $address;
		$this->port = $port;
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!@socket_connect($this->socket, $address, $port)) {
			throw new \Exception('Socket can\'t connect');
		}
		socket_set_nonblock($this->socket);
	}

	public function getIdentifier() {
		return $this->address . $this->port;
	}

	public function writeMessage($msg) {
		if (strlen($msg) > 0) {
			$data = zlib_encode($msg, ZLIB_ENCODING_DEFLATE, 7);
			@socket_write($this->socket, pack('N', strlen($data)) . $data);
		}
	}

	public function checkMessages() {
		$err = socket_last_error($this->socket);
		if ($err !== 0 && $err !== 35 && $err !== 11) {
			@socket_close($this->socket);
			return false;
		}		
		$data = $this->lastMessage;
		$this->lastMessage = '';
		while (strlen($buffer = @socket_read($this->socket, 65535, PHP_BINARY_READ)) > 0) {
			$data .= $buffer;
		}
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
					var_dump('Not zlib packet');
				}
				$offset += $len;
			}
		}
		return true;
	}

	private function checkPacket($buffer) {
		$buffer = zlib_decode($buffer);
		if ($buffer === FALSE) {
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
				$this->server->sendRawPacket($address, $port, $payload);
			}
		} else {
			echo 'UNKNOWN PACKET TYPE'.PHP_EOL;
			var_dump($buffer);
		}
		return true;
	}

	

}

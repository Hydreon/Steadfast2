<?php

namespace pocketmine\network\proxylib;

class RemoteProxyServer {

	private $proxyManager;
	private $socket;
	private $ip;
	private $port;
	private $lastBuffer = '';
	private $writeQueue = [];

	public function __construct($proxyManager, $socket) {
		$this->proxyManager = $proxyManager;
		$this->socket = $socket;
		socket_set_nonblock($this->socket);
		socket_getpeername($this->socket, $address, $port);
		$this->ip = $address;
		$this->port = $port;
		$this->proxyManager->getLogger()->notice("RemoteProxyServer [$address:$port] has connected.");
	}

	public function getIdentifier() {
		return $this->ip . ':' . $this->port;
	}

	public function close() {
		$this->proxyManager->getLogger()->notice("RemoteProxyServer [$this->ip:$this->port] has disconnected.");
		$info = chr(strlen($this->getIdentifier())) . $this->getIdentifier() . 'close';
		$this->proxyManager->getProxyServer()->pushToExternalQueue($info);
		socket_clear_error($this->socket);
		socket_close($this->socket);
		if (get_resource_type($this->socket) == 'Socket') {
			$err = socket_last_error($this->socket);
			var_dump('Close error :' . $err);
			return $err == 0;
		} else {
			return true;
		}
	}

	public function update() {
		$err = socket_last_error($this->socket);
		if ($err !== 0 && $err !== 35 && $err !== 11) {
			return false;
		} else {
			$this->checkReadQueue();
			$this->checkWriteQueue();
			return true;
		}
	}

	public function checkPacket($data) {
		$data = zlib_decode($data);
		if ($data === false) {
			return false;
		}	
		$info = chr(strlen($this->getIdentifier())) . $this->getIdentifier() . $data;
		$this->proxyManager->getProxyServer()->pushToExternalQueue($info);
		return true;
	}

	public function putPacket($buffer) {
		$data = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		$this->writeQueue[] = pack('N',  strlen($data)) . $data;
	}

	private function checkWriteQueue() {
		foreach ($this->writeQueue as $key => $data) {
			$dataLength = strlen($data);
			socket_clear_error($this->socket);
			while (true) {
				$sentBytes = socket_write($this->socket, $data);
				if ($sentBytes === false) {
					$errno = socket_last_error($this->socket);
					echo 'SOCKET WRITE ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
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
		$data = $this->lastBuffer;
		$atLeastOneRecived = false;
		while (strlen($buffer = @socket_read($this->socket, 65535, PHP_BINARY_READ)) > 0) {
			$data .= $buffer;
			$atLeastOneRecived = true;
		}

		if (!$atLeastOneRecived) {
			$errno = socket_last_error($this->socket);
			if ($errno !== 11) {
				echo 'SOCKET READ ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
			}
			return;
		}
		
		$this->lastBuffer = '';
		if (($dataLen = strlen($data)) > 0) {
			$offset = 0;
			while ($offset < $dataLen) {
				if ($offset + 4 > $dataLen) {
					$this->lastBuffer = substr($data, $offset);
					break;
				}
				$len = unpack('N', substr($data, $offset, 4));
				$len = $len[1];
				if ($offset + $len + 4 > $dataLen) {
					$this->lastBuffer = substr($data, $offset);
					break;
				}
				$offset += 4;
				$msg = substr($data, $offset, $len);
				$res = $this->checkPacket($msg);
				if (!$res) {
					var_dump('FATAL: Not zlib packet');
				}
				$offset += $len;
			}
		}
	}

	public function sendRawData($buffer) {
		$data = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		$data = pack('N', strlen($data)) . $data;
		socket_sendto($this->socket, $data, strlen($data), 0, $this->ip, $this->port);
	}

}

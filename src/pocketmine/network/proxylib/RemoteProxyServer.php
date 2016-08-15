<?php

namespace pocketmine\network\proxylib;

class RemoteProxyServer {

	private $proxyManager;
	private $socket;
	private $ip;
	private $port;
	private $lastBuffer = '';

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
		$info = array(
			'id' => $this->getIdentifier(),
			'data' => 'close'
		);
		$this->proxyManager->getProxyServer()->pushToExternalQueue(serialize($info));
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
			$data = $this->lastBuffer;
			$this->lastBuffer = '';
			
			$atLeastOneRecived = false;
			while (strlen($buffer = socket_read($this->socket, 65535, PHP_BINARY_READ)) > 0) {
				$data .= $buffer;
				$atLeastOneRecived = true;
			}
			
			if (!$atLeastOneRecived) {
				$errno = socket_last_error($this->socket);
				if ($errno !== 11) {
					echo 'SOCKET READ ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
				}
			}
			
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
					$this->checkPacket($msg);
					$offset += $len;
				}
			}

			return true;
		}
	}

	public function checkPacket($data) {
		$info = array(
			'id' => $this->getIdentifier(),
			'data' => zlib_decode($data),
		);
		$this->proxyManager->getProxyServer()->pushToExternalQueue(serialize($info));
	}

	public function putPacket($buffer) {
		$data = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		$dataLength = strlen($data);

		socket_clear_error($this->socket);
		while (true) {
			$sentBytes = socket_write($this->socket, pack('N', $dataLength) . $data);
			if ($sentBytes === false) {
				$errno = socket_last_error($this->socket);
				echo 'SOCKET WRITE ERROR: ' . $errno . ' - ' . socket_strerror($errno) . PHP_EOL;
				break;
			} else if ($sentBytes < $dataLength) {
				$buffer = substr($dataLength, $sentBytes);
				$dataLength -= $sentBytes;
			} else {
				break;
			}
		}
	}

	
	public function sendRawData($buffer) {
		$data = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		$data = pack('N', strlen($data)) . $data;
		socket_sendto($this->socket, $data, strlen($data), 0, $this->ip, $this->port);
	}

}

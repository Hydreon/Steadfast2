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
		@socket_close($this->socket);
	}

	public function update() {
		$err = socket_last_error($this->socket);
		if ($err !== 0 && $err !== 35 && $err !== 11) {
			return false;
		} else {
			$data = $this->lastBuffer;
			$this->lastBuffer = '';

			while (strlen($buffer = @socket_read($this->socket, 65535, PHP_BINARY_READ)) > 0) {
				$data .= $buffer;
			}
			if (($dataLen = strlen($data)) > 0) {
				$offset = 0;
				while ($offset < $dataLen) {
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
			'data' => zlib_decode($data)
		);
		$this->proxyManager->getProxyServer()->pushToExternalQueue(serialize($info));
	}

	public function putPacket($buffer) {
		$data = zlib_encode($buffer, ZLIB_ENCODING_DEFLATE, 7);
		@socket_write($this->socket, pack('N', strlen($data)) . $data);
	}

}

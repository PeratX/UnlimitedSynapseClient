<?php

/**
 * UnlimitedSynapseClient
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PeratX
 */

namespace us\client\network\protocol;

use sf\console\Logger;
use us\client\util\Binary;

class Session{
	private $receiveBuffer = "";
	/** @var resource */
	private $socket;
	private $address;
	private $port;
	/** @var SynapseClient */
	private $server;

	public function __construct(SynapseClient $server, SynapseSocket $socket){
		$this->server = $server;
		$this->socket = $socket;
		@socket_getpeername($this->socket->getSocket(), $address, $port);
		$this->address = $address;
		$this->port = $port;
		$this->run();
	}

	public function run(){
		$this->tickProcessor();
	}

	private function tickProcessor(){
		while(!$this->server->isShutdown()){
			$start = microtime(true);
			$this->tick();
			$time = microtime(true);
			if($time - $start < 0.01){
				@time_sleep_until($time + 0.01 - ($time - $start));
			}
		}
		$this->tick();
		$this->socket->close();
	}

	private function tick(){
		if($this->update()){
			if(($data = $this->readPacket()) !== null){
				foreach($data as $pk){
					$this->server->pushThreadToMainPacket($pk);
				}
			}
			while(strlen($data = $this->server->readMainToThreadPacket()) > 0){
				$this->writePacket($data);
			}
		}else{
			$this->server->pushThreadToMainPacket("disconnected");
		}
	}

	public function getHash(){
		return $this->address . ':' . $this->port;
	}

	public function getAddress(): string{
		return $this->address;
	}

	public function getPort(): int{
		return $this->port;
	}

	public function update(): bool{
		$err = socket_last_error($this->socket->getSocket());
		socket_clear_error($this->socket->getSocket());
		if($err == 10057 or $err == 10054){
			Logger::error("Synapse connection has disconnected unexpectedly");
			return false;
		}else{
			$data = @socket_read($this->socket->getSocket(), 65535, PHP_BINARY_READ);
			if($data != ""){
				$this->receiveBuffer .= $data;
			}
			return true;
		}
	}

	public function getSocket(){
		return $this->socket;
	}

	public function readPacket(){
		$packets = [];
		if($this->receiveBuffer !== "" && strlen($this->receiveBuffer) > 0){
			$len = strlen($this->receiveBuffer);
			$offset = 0;
			while($offset < $len){
				if($offset > $len - 4) break;
				$pkLen = Binary::readInt(substr($this->receiveBuffer, $offset, 4));
				$offset += 4;

				if($pkLen <= ($len - $offset)){
					$buf = substr($this->receiveBuffer, $offset, $pkLen);
					$offset += $pkLen;

					$packets[] = $buf;
				}else{
					$offset -= 4;
					break;
				}
			}
			if($offset < $len){
				$this->receiveBuffer = substr($this->receiveBuffer, $offset);
			}else{
				$this->receiveBuffer = "";
			}
		}

		return $packets;
	}

	public function writePacket($data){
		@socket_write($this->socket->getSocket(), Binary::writeInt(strlen($data)) . $data);
	}
}

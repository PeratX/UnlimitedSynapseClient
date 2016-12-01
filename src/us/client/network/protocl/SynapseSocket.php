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

class SynapseSocket{
	private $socket;
	private $interface;
	private $port;

	public function __construct($port = 10305, $interface = "127.0.0.1"){
		$this->interface = $interface;
		$this->port = $port;
		$this->connect();
	}

	public function connect(){
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket === false or !@socket_connect($this->socket, $this->interface, $this->port)){
			Logger::critical("Synapse Client can't connect $this->interface:$this->port");
			Logger::error("Socket error: " . socket_strerror(socket_last_error()));
			return false;
		}
		Logger::info("Synapse has connected to $this->interface:$this->port");
		socket_set_nonblock($this->socket);
		return true;
	}

	public function getSocket(){
		return $this->socket;
	}

	public function close(){
		socket_close($this->socket);
	}
}
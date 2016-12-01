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
 
namespace us\client\network;

use sf\Framework;
use us\client\network\packet\DataPacket;
use us\client\network\protocol\SynapseClient;

class SynapseInterface{
	private $synapse;
	private $ip;
	private $port;
	/** @var SynapseClient */
	private $client;
	/** @var DataPacket[] */
	private $packetPool = [];
	private $connected = true;
	
	public function __construct(Synapse $server, string $ip, int $port){
		$this->synapse = $server;
		$this->ip = $ip;
		$this->port = $port;
		$this->registerPackets();
		$this->client = new SynapseClient(Framework::getInstance()->getLoader()), $port, $ip);
	}

	public function shutdown(){
		$this->client->shutdown();
	}

	public function putPacket(DataPacket $pk){
		if(!$pk->isEncoded){
			$pk->encode();
		}
		$this->client->pushMainToThreadPacket($pk->buffer);
	}

	public function isConnected() : bool{
		return $this->connected;
	}

	public function process(){
		while(strlen($buffer = $this->client->readThreadToMainPacket()) > 0){
			$this->handlePacket($buffer);
		}
	}

	/**
	 * @param $buffer
	 *
	 * @return DataPacket
	 */
	public function getPacket($buffer) {
		$pid = ord($buffer{0});
		/** @var DataPacket $class */
		$class = $this->packetPool[$pid];
		if ($class !== null) {
			$pk = clone $class;
			$pk->setBuffer($buffer, 1);
			return $pk;
		}
		return null;
	}

	public function handlePacket($buffer){
		if(($pk = $this->getPacket($buffer)) != null){
			$pk->decode();
			$this->synapse->handleDataPacket($pk);
		}
	}

	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket($id, $class) {
		$this->packetPool[$id] = new $class;
	}


	private function registerPackets() {
		$this->packetPool = new \SplFixedArray(256);

	}
}

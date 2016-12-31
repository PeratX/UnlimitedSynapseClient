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
use us\client\Client;
use us\client\network\packet\DataPacket;
use us\client\network\protocol\SynapseClient;

class SynapseInterface{
	private $client;
	private $address;
	private $port;
	/** @var SynapseClient */
	private $synapseClient;
	/** @var DataPacket[] */
	private $packetPool = [];
	
	public function __construct(Client $client, string $address, int $port){
		$this->client = $client;
		$this->address = $address;
		$this->port = $port;
		$this->packetPool = new \SplFixedArray(256);
		$this->synapseClient = new SynapseClient(Framework::getInstance()->getLoader(), $port, $address);
	}

	public function shutdown(){
		$this->synapseClient->shutdown();
	}

	public function putPacket(DataPacket $pk){
		if(!$pk->isEncoded){
			$pk->encode();
		}
		$this->synapseClient->pushMainToThreadPacket($pk->buffer);
	}

	public function process(){
		while(strlen($buffer = $this->synapseClient->readThreadToMainPacket()) > 0){
			switch($buffer){
				case "disconnected":
					$this->client->close(Client::CLOSE_REASON_DISCONNECT);
					break;
				default:
					$this->handlePacket($buffer);
			}
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
			$this->client->handleDataPacket($pk);
		}
	}

	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket($id, $class) {
		$this->packetPool[$id] = new $class;
	}
}

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

namespace us\client;

use us\client\network\packet\DataPacket;

abstract class Client{
	const CLOSE_REASON_NONE = 0;
	const CLOSE_REASON_DISCONNECT = 1;

	public abstract function handleDataPacket(DataPacket $packet);

	public abstract function close(int $reason);
}
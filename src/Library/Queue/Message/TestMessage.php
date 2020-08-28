<?php

/**
 * File: TestMessage.php
 * File Created: Thursday, 28th May 2020 7:24:41 pm
 * Author: Yin
 */

namespace TheFairLib\Library\Queue\Message;

class TestMessage extends BaseMessage
{
	protected $name;
	protected $address;

	protected $messageType = 'test';

	/**
	 * Get the value of name
	 *
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the value of name
	 *
	 * @param mixed $name
	 *
	 * @return self
	 */
	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Get the value of address
	 *
	 * @return mixed
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * Set the value of address
	 *
	 * @param mixed $address
	 *
	 * @return self
	 */
	public function setAddress($address)
	{
		$this->address = $address;

		return $this;
	}
}

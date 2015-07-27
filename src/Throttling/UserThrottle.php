<?php namespace Digbang\Security\Throttling;

use Digbang\Security\Users\User;

class UserThrottle extends Throttle
{
	/**
	 * @type User
	 */
	private $user;

	/**
	 * @param User $user
	 */
	public function __construct(User $user)
	{
		$this->user = $user;
	}

	/**
	 * Returns the associated user with the throttler.
	 *
	 * @return \Digbang\Security\Users\User
	 */
	public function getUser()
	{
		return $this->user;
	}
}
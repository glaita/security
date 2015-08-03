<?php namespace Digbang\Security\Permissions;

use Doctrine\Common\Collections\Collection;

trait LazyPermissionsTrait
{
	/**
	 * @type Collection
	 */
	protected $permissions;

	/**
	 * @param Collection $permissions
	 * @param array      $secondaryPermissions
	 */
	abstract protected function mergePermissions(Collection $permissions, array $secondaryPermissions = []);

	/**
	 * Returns if access is available for all given permissions.
	 *
	 * @param  array|string $permissions
	 *
	 * @return bool
	 */
	public function hasAccess($permissions)
	{
		if (func_num_args() > 1)
		{
			$permissions = func_get_args();
		}

		foreach ((array) $permissions as $permissionName)
		{
			if (! $this->allows($permissionName))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns if access is available for any given permissions.
	 *
	 * @param  array|string $permissions
	 *
	 * @return bool
	 */
	public function hasAnyAccess($permissions)
	{
		if (func_num_args() > 1)
		{
			$permissions = func_get_args();
		}

		foreach ((array) $permissions as $permissionName)
		{
			if ($this->allows($permissionName))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds a permission to the merged permissions Collection.
	 * Override logic is handled from outside to enable strict or standard permissions.
	 *
	 * @param Permission $permission
	 * @param bool       $override
	 */
	protected function add(Permission $permission, $override = true)
	{
		if ($override || ! $this->permissions->containsKey($permission->getName()))
		{
			$this->permissions->set($permission->getName(), $permission);
		}
	}

	/**
	 * Check if the given permission is allowed.
	 * Only explicitly allowed permissions will return true.
	 *
	 * @param string $permissionName
	 * @return bool
	 */
	protected function allows($permissionName)
	{
		foreach ($this->getMatchingPermissions($permissionName) as $key)
		{
			/** @type Permission $permission */
			$permission = $this->permissions->get($key);

			if ($permission->isAllowed())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $permission
	 * @return array
	 */
	protected function getMatchingPermissions($permission)
	{
		return array_filter($this->permissions->getKeys(), function($key) use ($permission){
			return str_is($permission, $key) || str_is($key, $permission);
		});
	}
}
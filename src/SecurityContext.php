<?php namespace Digbang\Security;

use Digbang\Doctrine\Metadata\DecoupledMappingDriver;
use Digbang\Doctrine\Metadata\EntityMapping;
use Digbang\Security\Configurations\SecurityContextConfiguration;
use Digbang\Security\Factories\SecurityFactory;

final class SecurityContext
{
	/**
	 * @type SecurityFactory
	 */
	private $securityFactory;

	/**
	 * @type DecoupledMappingDriver
	 */
	private $mappingDriver;

	/**
	 * Configured contexts
	 * @type array
	 */
	private $contexts = [];

	/**
	 * Flyweight Security instances.
	 * @type array
	 */
	private $instances = [];

	/**
	 * SecurityContext constructor.
	 *
	 * @param SecurityFactory        $securityFactory
	 * @param DecoupledMappingDriver $mappingDriver
	 */
	public function __construct(SecurityFactory $securityFactory, DecoupledMappingDriver $mappingDriver)
	{
		$this->securityFactory = $securityFactory;
		$this->mappingDriver   = $mappingDriver;
	}

	/**
	 * Add a security context.
	 *
	 * @param string                       $context
	 * @param SecurityContextConfiguration $configuration
	 * @throws \BadMethodCallException
	 */
	public function add($context, SecurityContextConfiguration $configuration)
	{
		$this->contexts[$context] = $configuration;

		$this->updateMappings($configuration);
	}

	/**
	 * Get the Security instance for the given context.
	 *
	 * @param string $context
	 * @return Security
	 */
	public function getSecurity($context)
	{
		if (! array_key_exists($context, $this->contexts))
		{
			throw new \InvalidArgumentException("Context [$context] is not configured.");
		}

		if (array_key_exists($context, $this->instances))
		{
			return $this->instances[$context];
		}

		return $this->instances[$context] = $this->securityFactory->create($context ,$this->contexts[$context]);
	}

	/**
	 * @return array
	 */
	public function getConfigurations()
	{
		return $this->contexts;
	}

	/**
	 * @param SecurityContextConfiguration $configuration
	 * @throws \BadMethodCallException
	 */
	private function updateMappings(SecurityContextConfiguration $configuration)
	{
		$mappings = $configuration->getMappings();

		if (! $configuration->isRolesEnabled())
		{
			unset($mappings['role']);

			$this->validateAndCall($mappings['user'], 'disableRoles');
		}

		if (! $configuration->isThrottlesEnabled())
		{
			unset(
				$mappings['throttle'],
				$mappings['ipThrottle'],
				$mappings['globalThrottle'],
				$mappings['userThrottle']
			);

			$this->validateAndCall($mappings['user'], 'disableThrottles');
		}

		if (! $configuration->isPermissionsEnabled())
		{
			unset($mappings['permission']);

			$this->validateAndCall($mappings['user'], 'disablePermissions');

			if (isset($mappings['role']))
			{
				$this->validateAndCall($mappings['role'], 'disablePermissions');
			}
		}

		foreach ($mappings as $mapping)
		{
			$this->mappingDriver->addMapping($this->makeMapping($mapping));
		}
	}

	private function makeMapping($mapping)
	{
		if ($mapping instanceof EntityMapping)
		{
			return $mapping;
		}

		return new $mapping;
	}

	/**
	 * @param EntityMapping|string &$mapping
	 * @param string $method
	 * @param ...$params
	 * @return mixed
	 *
	 * @throws \BadMethodCallException
	 */
	private function validateAndCall(&$mapping, $method, ...$params)
	{
		$mapping = $this->makeMapping($mapping);

		if (! method_exists($mapping, $method))
		{
			throw new \BadMethodCallException("EntityMapping [" . get_class($mapping) .
				"] does not implement '$method'."
			);
		}

		return call_user_func_array([$mapping, $method], $params);
	}
}
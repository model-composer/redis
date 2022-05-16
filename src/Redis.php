<?php namespace Model\Redis;

use Model\Config\Config;

class Redis
{
	private static \RedisCluster|\Redis $redis;

	/**
	 * Redis client factory
	 *
	 * @return \RedisCluster|\Redis|null
	 */
	public static function getClient(): \RedisCluster|\Redis|null
	{
		if (!isset(self::$redis)) {
			$config = self::getConfig();

			if (!$config['enabled'])
				throw new \Exception('Redis is disabled');

			if ($config['cluster']) {
				self::$redis = new \RedisCluster(null, [$config['host'] . ':' . $config['port']]);
			} else {
				self::$redis = new \Redis();
				self::$redis->connect($config['host'], $config['port']);
			}

			if ($config['password'] ?? null)
				self::$redis->auth($config['password']);
		}

		return self::$redis;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public static function isEnabled(): bool
	{
		$config = self::getConfig();
		return $config['enabled'];
	}

	/**
	 * @return string|null
	 * @throws \Exception
	 */
	public static function getNamespace(): ?string
	{
		$config = self::getConfig();
		return $config['namespace'];
	}

	/**
	 * Magic method for Redis methods
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $arguments): mixed
	{
		$config = self::getConfig();
		if (!empty($arguments[0]) and $config['namespace'] ?? null)
			$arguments[0] = $config['namespace'] . ':' . $arguments[0];

		return call_user_func_array([self::getClient(), $name], $arguments);
	}

	/**
	 * Config retriever
	 *
	 * @return array
	 * @throws \Exception
	 */
	private static function getConfig(): array
	{
		return Config::get('redis', [
			[
				'version' => '0.3.0',
				'migration' => function (array $config, string $env) {
					if ($config) // Already existing
						return $config;

					return [
						'enabled' => true,
						'cluster' => false,
						'host' => '127.0.0.1',
						'port' => 6379,
						'password' => null,
						'namespace' => null,
					];
				},
			],
		]);
	}
}

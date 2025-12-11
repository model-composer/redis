<?php namespace Model\Redis;

use Model\Config\Config;

class Redis
{
	private static array $connections = [];

	/**
	 * Redis client factory
	 *
	 * @param string $host
	 * @return \RedisCluster|\Redis|null
	 */
	public static function getClient(string $host = 'main'): \RedisCluster|\Redis|null
	{
		if (!isset(self::$connections[$host])) {
			$config = Config::get('redis');

			if (!$config[$host]['enabled'])
				throw new \Exception('Redis is disabled');

			if ($config[$host]['cluster']) {
				self::$connections[$host] = new \RedisCluster(null, [$config[$host]['host'] . ':' . $config[$host]['port']]);
			} else {
				self::$connections[$host] = new \Redis();
				self::$connections[$host]->connect($config[$host]['host'], $config[$host]['port']);
			}

			if ($config[$host]['password'] ?? null)
				self::$connections[$host]->auth($config[$host]['password']);
		}

		return self::$connections[$host];
	}

	/**
	 * @param string $host
	 * @return bool
	 * @throws \Exception
	 */
	public static function isEnabled(string $host = 'main'): bool
	{
		$config = Config::get('redis');
		return $config[$host]['enabled'];
	}

	/**
	 * @param string $host
	 * @return string|null
	 * @throws \Exception
	 */
	public static function getNamespace(string $host = 'main'): ?string
	{
		$config = Config::get('redis');
		return $config[$host]['namespace'];
	}

	/**
	 * Magic method for Redis methods (only for main host)
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $arguments): mixed
	{
		$config = Config::get('redis');
		if (!empty($arguments[0]) and $config['main']['namespace'] ?? null)
			$arguments[0] = $config['main']['namespace'] . ':' . $arguments[0];

		return call_user_func_array([self::getClient(), $name], $arguments);
	}
}

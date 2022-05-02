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
	private static function getClient(): \RedisCluster|\Redis|null
	{
		if (!isset(self::$redis)) {
			$config = self::getConfig();

			if ($config['host'] === 'session') // For development purposes
				return null;

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
	 * Magic method for Redis methods
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $arguments): mixed
	{
		$config = self::getConfig();

		if ($config['host'] === 'session') { // For development purposes
			switch ($name) {
				case 'get':
					return $_SESSION['redis:' . $arguments[0]] ?? null;

				case 'set':
					$_SESSION['redis:' . $arguments[0]] = $arguments[1];
					return true;
			}
		}

		if (!empty($arguments[0]) and $config['prefix'] ?? null)
			$arguments[0] = $config['prefix'] . ':' . $arguments[0];

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
		return Config::get('redis', function () {
			return [
				'cluster' => false,
				'host' => '127.0.0.1',
				'port' => 6379,
				'password' => null,
				'prefix' => null,
			];
		});
	}
}

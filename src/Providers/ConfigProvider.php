<?php namespace Model\Redis\Providers;

use Model\Config\AbstractConfigProvider;

class ConfigProvider extends AbstractConfigProvider
{
	public static function migrations(): array
	{
		return [
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
			[
				'version' => '0.3.6',
				'migration' => function (array $config, string $env) {
					return [
						'hosts' => [
							'main' => $config,
						],
					];
				},
			],
		];
	}

	public static function templating(): array
	{
		return [
			'host',
			'port',
			'password',
		];
	}
}

<?php
declare(strict_types=1);

namespace Test\DD\Md\JsonValidator\Unit;

use DD\Md\JsonValidator\Adapter\Psr7Adapter;
use DD\Md\JsonValidator\JsonSchemaManager;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr15JsonSchema;

class Psr7AdapterTest extends TestCase
{

	public function testGetSchemaManager(): void
	{
		$configDir = realpath(__DIR__ . '/../config');
		$manager = new JsonSchemaManager($configDir);
		$manager->addRelFile('profile.yml');

		$adapter = new Psr7Adapter($manager);
		self::assertCount(1, $adapter->getSchemaManager()->getConfig(['profile']));
	}

	public function testValidate(): void
	{
		$configDir = realpath(__DIR__ . '/../config');
		$manager = new JsonSchemaManager($configDir);
		$manager->addRelFile('profile.yml');

		$adapter = new Psr7Adapter($manager);

		$body = json_decode('{"id":1}', false, 512, JSON_THROW_ON_ERROR);
		$adapter->validate($body, ['profile', 'request', 'query']);
		self::assertCount(1, $adapter->getSchemaManager()->getConfig(['profile']));
	}
}
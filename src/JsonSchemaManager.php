<?php
declare(strict_types=1);

namespace DD\Md\JsonValidator;

use RuntimeException;
use Symfony\Component\Yaml\Parser;

class JsonSchemaManager
{
	protected array $configTree = [];
	/** @var string[] */
	protected array $files = [];

	protected string $configsDir;
	protected Parser $parserYaml;

	public function __construct(string $validatorsDir)
	{
		$this->configsDir = $validatorsDir;
		$this->parserYaml = new Parser;
	}

	/**
	 * Получает ветку конфигурации из дерева конфигурации
	 *
	 * @param string[] $names
	 * @param array|null $context
	 *
	 * @return array
	 */
	public function getConfig(array $names, array $context = null): array
	{
		$context = $context ?? $this->configTree;
		$levelName = array_shift($names);

		$newContext = $context[$levelName] ?? false;
		if ($newContext === false) {
			return [];
		}

		return count($names) ? $this->getConfig($names, $newContext) : $newContext;
	}

	public function addFile(string $path): void
	{
		if (in_array($path, $this->files, true)) {
			return;
		}

		if (!file_exists($path)) {
			throw new RuntimeException("Can`t find config file `$path`", 500);
		}

		$this->files[] = $path;

		$fileConfigs = $this->parserYaml->parseFile($path);
		foreach ($fileConfigs as $name => $validatorConfig) {
			if ($this->configTree[$name] ?? null) {
				throw new RuntimeException("Validator `$name` already defined", 500);
			}

			$this->configTree[$name] = $validatorConfig;
		}
	}

	/**
	 * Добавить yml файл с конфигурацией относительно директории с конфигами
	 *
	 * @param string $configFile
	 * @throws RuntimeException
	 */
	public function addRelFile(string $configFile): void
	{
		$path = $this->configsDir . '/' . $configFile;
		$this->addFile($path);
	}
}
<?php
declare(strict_types=1);

use DD\Md\JsonValidator\Adapter\Psr7Adapter;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class Psr15JsonSchema implements MiddlewareInterface
{
	protected Psr7Adapter $validator;
	protected LoggerInterface $logger;
	protected Closure $resolver;

	public function __construct(Psr7Adapter $validator, LoggerInterface $logger, callable $resolver)
	{
		$this->validator = $validator;
		$this->logger = $logger;
		$this->resolver = $resolver;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
	{
		$validatorName = call_user_func($this->resolver, $request);
		if ($validatorName === null) {
			return $next->handle($request);
		}

		$errors = $this->validator->validateRequest($request, $validatorName);
		if (count($errors)) {
			return new JsonResponse(['errors' => ['validator' => $errors]], 400);
		}

		$response = $next->handle($request);

		$errors = $this->validator->validateResponse($response, $validatorName);
		$countErrors = count($errors);
		if ($countErrors) {
			$this->logger->error('Ошибка валидации json ответа', [
				'errors' => $errors,
				'name' => $validatorName
			]);
			$response = $response->withHeader('x-vre', $countErrors);
		}

		return $response;
	}
}
<?php

declare(strict_types=1);

namespace SunnyPHP\Exception;

use Psr\Log\LoggerInterface;
use SunnyPHP\Config\Repository;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;
use SunnyPHP\Validation\ValidationException;
use Throwable;

final class Handler
{
    public function __construct(
        private Repository $config,
        private LoggerInterface $logger,
    ) {
    }

    public function render(Request $request, Throwable $e): Response
    {
        $status = $e instanceof HttpException ? $e->status : 500;
        $debug = (bool) $this->config->get('app.debug', false);

        $payload = [
            'error' => $status >= 500 && !$debug
                ? 'Internal Server Error'
                : ($e->getMessage() !== '' ? $e->getMessage() : 'Error'),
        ];

        if ($e instanceof ValidationException) {
            $payload['errors'] = $e->errors;
        }

        if ($debug) {
            $payload['exception'] = $e::class;
            $payload['message'] = $e->getMessage();
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
        }

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return Response::json($payload, $status);
    }
}

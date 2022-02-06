<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use JsonSerializable;
use Psr\Http\Message\ResponseFactoryInterface as Psr17ResponseFactory;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamFactoryInterface as Psr17StreamFactory;
use Snicco\Component\HttpRouting\Http\Responsable;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use stdClass;

interface ResponseFactory extends Psr17ResponseFactory, Psr17StreamFactory
{

    /**
     * @param string|array|Response|Psr7Response|stdClass|JsonSerializable|Responsable $response
     */
    public function toResponse($response): Response;

    public function make(int $status_code = 200, string $reason_phrase = ''): Response;

    public function html(string $html, int $status_code = 200): Response;

    /**
     * @param mixed $content Anything that can be passed to {@see \json_encode()}
     */
    public function json($content, int $status_code = 200): Response;

    public function redirect(string $location, int $status_code = 302): RedirectResponse;

    public function noContent(): Response;

    public function delegate(bool $should_headers_be_sent = true): DelegatedResponse;

}
<?php
/**
 * This code is licensed under the MIT License.
 *
 * Copyright (c) 2018-2020 Alexey Kopytko <alexey@kopytko.com> and contributors
 * Copyright (c) 2018 Appwilio (http://appwilio.com), greabock (https://github.com/greabock), JhaoDa (https://github.com/jhaoda)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

declare(strict_types=1);

namespace CommonSDK\Types;

use CommonSDK\Contracts\Response;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class HTTPErrorResponse implements Response, ResponseInterface
{
    private ResponseInterface $response;

    private function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public static function withHTTPResponse(ResponseInterface $response): self
    {
        return new self($response);
    }

    public function hasErrors(): bool
    {
        return $this->response->getStatusCode() !== HttpResponse::HTTP_OK;
    }

    public function getMessages()
    {
        yield new Message($this->response->getReasonPhrase(), (string) $this->response->getStatusCode());
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $code
     * @param mixed $reasonPhrase
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code, $reasonPhrase);
    }

    /**
     * @psalm-suppress MixedArgument
     *
     * @param mixed $name
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $version
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $name
     */
    public function withoutHeader(string $name): MessageInterface
    {
        return $this->response->withoutHeader($name);
    }

    /**
     * @psalm-suppress MixedArgument
     *
     * @param mixed $name
     */
    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->response->withHeader($name, $value);
    }

    /**
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->response->withBody($body);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * @psalm-suppress MixedArgument
     *
     * @param mixed $name
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $name
     * @param mixed $value
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }
}

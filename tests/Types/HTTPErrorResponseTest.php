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

namespace Tests\CommonSDK\Types;

use CommonSDK\Types\HTTPErrorResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * @covers \CommonSDK\Types\HTTPErrorResponse
 */
class HTTPErrorResponseTest extends TestCase
{
    public function test_create(): void
    {
        $body = $this->createMock(StreamInterface::class);

        $response = HTTPErrorResponse::withHTTPResponse(new class($body) implements ResponseInterface {

            private int $status_code = HttpResponse::HTTP_BAD_GATEWAY;
            private string $status_reason = 'Bad Gateway Testing 123';
            private string $version = '1.1';
            private array $headers = ['foo' => ['bar']];
            private StreamInterface $body;

            public function __construct(StreamInterface $body) {
                $this->body = $body;
            }

            public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
            {
                $new = clone $this;
                $new->status_code = $code;
                $new->status_reason = $reasonPhrase;
                return $new;
            }

            public function hasHeader(string $name): bool
            {
                return isset($this->headers[$name]);
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }

            public function getBody(): StreamInterface
            {
                return $this->body;
            }

            public function withProtocolVersion(string $version): MessageInterface
            {
                $new = clone $this;
                $new->version = $version;
                return $new;
            }

            public function withoutHeader(string $name): MessageInterface
            {
                $new = clone $this;
                unset($new->headers[$name]);
                return $new;
            }

            public function getHeaderLine(string $name): string
            {
                return isset($this->headers[$name]) ? implode(', ', $this->headers[$name]) : '';
            }

            public function withHeader(string $name, $value): MessageInterface
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];
                return $new;
            }

            public function withBody(StreamInterface $body): MessageInterface
            {
                $new = clone $this;
                $new->body = $body;
                return $new;
            }

            public function getReasonPhrase(): string
            {
                return $this->status_reason;
            }

            public function getHeader(string $name): array
            {
                return $this->headers[$name] ?? [];
            }

            public function getProtocolVersion(): string
            {
                return $this->version;
            }

            public function getStatusCode(): int
            {
                return $this->status_code;
            }

            public function withAddedHeader(string $name, $value): MessageInterface
            {
                $new = clone $this;
                $new->headers[$name] = is_array($value) ? $value : [$value];
                return $new;
            }
        });


        $this->assertTrue($response->hasErrors());
        $this->assertCount(1, $response->getMessages());
        foreach ($response->getMessages() as $message) {
            $this->assertSame('502', $message->getErrorCode());
            $this->assertSame('Bad Gateway Testing 123', $message->getMessage());
        }

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame('Bad Gateway Testing 123', $response->getReasonPhrase());

        $withStatus = $response->withStatus(200, 'b');
        $this->assertSame([200, 'b'], [$withStatus->getStatusCode(), $withStatus->getReasonPhrase()]);

        $this->assertTrue($response->hasHeader('foo'));

        $this->assertSame(['foo' => ['bar']], $response->getHeaders());
        $this->assertSame('', (string) $response->getBody());

        $withVersion = $response->withProtocolVersion("1.1");
        $this->assertSame("1.1", $withVersion->getProtocolVersion());

        $withoutHeader = $response->withoutHeader('foo');
        $this->assertSame('', $withoutHeader->getHeaderLine('foo'));
        $this->assertSame([], $withoutHeader->getHeader('foo'));

        $this->assertSame(['bar'], $response->getHeader('foo'));

        $withHeader = $response->withHeader('c', 'd');
        $this->assertSame(['d'], $withHeader->getHeader('c'));

        $body = $this->createMock(StreamInterface::class);
        $withBody = $response->withBody($body);
        $this->assertSame($body, $withBody->getBody());

        $this->assertSame('Bad Gateway Testing 123', $response->getReasonPhrase());
        $this->assertSame(['bar'], $response->getHeader('foo'));

        $this->assertSame("1.1", $response->getProtocolVersion());
        $this->assertSame(502, $response->getStatusCode());

        $withHeader = $response->withAddedHeader('e', 'f');
        $this->assertSame(['f'], $withHeader->getHeader('e'));

        $this->assertSame('{}', \json_encode($response));
    }

    public function test_not_an_error(): void
    {
        $response = HTTPErrorResponse::withHTTPResponse(new class() implements ResponseInterface {
            public function withStatus($code, $reasonPhrase = '')
            {
                return [$code, $reasonPhrase];
            }

            public function hasHeader($name)
            {
                return true;
            }

            public function getHeaders()
            {
                return ['foo' => 'bar'];
            }

            public function getBody()
            {
                return 'foo';
            }

            public function withProtocolVersion($version)
            {
                return $version;
            }

            public function withoutHeader($name)
            {
                return $name;
            }

            public function getHeaderLine($name)
            {
                return $name;
            }

            public function withHeader($name, $value)
            {
                return [$name, $value];
            }

            public function withBody(StreamInterface $body)
            {
                return $body;
            }

            public function getReasonPhrase()
            {
                return 'Bad Gateway Testing 123';
            }

            public function getHeader($name)
            {
                return $name;
            }

            public function getProtocolVersion()
            {
                return 1000;
            }

            public function getStatusCode()
            {
                return 200;
            }

            public function withAddedHeader($name, $value)
            {
                return [$name, $value];
            }
        });

        $this->assertFalse($response->hasErrors());
    }
}

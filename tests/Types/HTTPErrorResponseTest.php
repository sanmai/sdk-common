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
        // Create a mock of the PSR-7 ResponseInterface.
        $mockResponse = $this->createMock(ResponseInterface::class);

        // Set up your expectations or default return values
        $mockResponse->method('withStatus')
            ->willReturnCallback(function ($code, $reasonPhrase = '') {
                return [$code, $reasonPhrase];
            });

        $mockResponse->method('hasHeader')
            ->willReturn(true);

        $mockResponse->method('getHeaders')
            ->willReturn(['foo' => 'bar']);

        $mockResponse->method('getBody')
            ->willReturn('foo');

        $mockResponse->method('withProtocolVersion')
            ->willReturnCallback(function ($version) {
                return $version;
            });

        $mockResponse->method('withoutHeader')
            ->willReturnCallback(function ($name) {
                return $name;
            });

        $mockResponse->method('getHeaderLine')
            ->willReturnCallback(function ($name) {
                return $name;
            });

        $mockResponse->method('withHeader')
            ->willReturnCallback(function ($name, $value) {
                return [$name, $value];
            });

        $mockResponse->method('withBody')
            ->willReturnCallback(function (StreamInterface $body) {
                return $body;
            });

        $mockResponse->method('getReasonPhrase')
            ->willReturn('Bad Gateway Testing 123');

        $mockResponse->method('getHeader')
            ->willReturnCallback(function ($name) {
                return $name;
            });

        $mockResponse->method('getProtocolVersion')
            ->willReturn(1000);

        $mockResponse->method('getStatusCode')
            ->willReturn(200);

        $mockResponse->method('withAddedHeader')
            ->willReturnCallback(function ($name, $value) {
                return [$name, $value];
            });

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

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

        $withVersion = $response->withProtocolVersion('1.1');
        $this->assertSame('1.1', $withVersion->getProtocolVersion());

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

        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame(502, $response->getStatusCode());

        $withHeader = $response->withAddedHeader('e', 'f');
        $this->assertSame(['f'], $withHeader->getHeader('e'));

        $this->assertSame('{}', json_encode($response));
    }

    public function test_not_an_error(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertFalse($response->hasErrors());
    }
}

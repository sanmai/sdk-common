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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @covers \CommonSDK\Types\HTTPErrorResponse
 */
class HTTPErrorResponseTest extends TestCase
{
    public function test_has_errors()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(502);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertTrue($response->hasErrors());
    }

    public function test_not_an_error(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects($this->once())->method('getStatusCode')->willReturn(200);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertFalse($response->hasErrors());
    }

    public function test_messages()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->atLeastOnce())
            ->method('getReasonPhrase')
            ->willReturn('Bad Gateway');

        $mockResponse->expects($this->atLeastOnce())
            ->method('getStatusCode')
            ->willReturn(502);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertCount(1, iterator_to_array($response->getMessages()));
        foreach ($response->getMessages() as $message) {
            $this->assertSame('502', $message->getErrorCode());
            $this->assertSame('Bad Gateway', $message->getMessage());
        }
    }

    public function test_with_status()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())->method('withStatus')
            ->with(500, 'Boo')
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withStatus(500, 'Boo'));
    }

    public function test_has_header()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('hasHeader')
            ->with('foo')
            ->willReturn(true);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertTrue($response->hasHeader('foo'));
    }

    public function test_get_headers()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['foo' => ['bar']]);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame(['foo' => ['bar']], $response->getHeaders());
    }

    public function test_get_body()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockStream = $this->createMock(StreamInterface::class);

        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($mockStream);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockStream, $response->getBody());
    }

    public function test_with_protocol_version()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('withProtocolVersion')
            ->with('1.1')
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withProtocolVersion('1.1'));
    }

    public function test_without_header()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('withoutHeader')
            ->with('foo')
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withoutHeader('foo'));
    }

    public function test_get_header_line()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getHeaderLine')
            ->with('foo')
            ->willReturn('bar');

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame('bar', $response->getHeaderLine('foo'));
    }

    public function test_with_header()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('withHeader')
            ->with('foo', 'bar')
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withHeader('foo', 'bar'));
    }

    public function test_get_header()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getHeader')
            ->with('foo')
            ->willReturn(['bar']);

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame(['bar'], $response->getHeader('foo'));
    }

    public function test_with_body()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockStream = $this->createMock(StreamInterface::class);

        $mockResponse->expects($this->once())
            ->method('withBody')
            ->with($mockStream)
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withBody($mockStream));
    }

    public function test_get_reason_phrase()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getReasonPhrase')
            ->willReturn('Bad Gateway');

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame('Bad Gateway', $response->getReasonPhrase());
    }

    public function test_get_protocol_version()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('getProtocolVersion')
            ->willReturn('1.1');

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame('1.1', $response->getProtocolVersion());
    }

    public function test_with_added_header()
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        $mockResponse->expects($this->once())
            ->method('withAddedHeader')
            ->with('foo', 'bar')
            ->willReturnSelf();

        $response = HTTPErrorResponse::withHTTPResponse($mockResponse);

        $this->assertSame($mockResponse, $response->withAddedHeader('foo', 'bar'));
    }
}

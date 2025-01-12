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

namespace CommonSDK\Tests\Common;

use CommonSDK\Contracts\HasErrorCode;
use CommonSDK\Contracts\Request;
use CommonSDK\Contracts\Response;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function Pipeline\take;
use function Pipeline\zip;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

abstract class ClientTestCase extends TestCase implements Concerns\ClientTestCase
{
    protected const DEFAULT_JSON = '[]';

    protected $lastRequestOptions = [];

    /**
     * @return MockObject|ResponseInterface
     */
    public function getResponse(string $contentType = 'application/json', ?string $responseBody = null, array $extraHeaders = [])
    {
        $responseBody = $responseBody ?? static::DEFAULT_JSON;

        $extraHeaders['Content-Type'] = $contentType;

        $response = $this->createMock(ResponseInterface::class);
        $response->method('hasHeader')->will($this->returnCallback(function ($headerName) use ($extraHeaders) {
            return \array_key_exists($headerName, $extraHeaders);
        }));
        $response->method('getHeader')->will($this->returnCallback(function ($headerName) use ($extraHeaders) {
            return [$extraHeaders[$headerName]];
        }));

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($responseBody);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }

    /**
     * @return MockObject|ClientInterface
     */
    public function getHttpClient(string $contentType = 'application/json', ?string $responseBody = null, array $extraHeaders = [])
    {
        $responseBody = $responseBody ?? static::DEFAULT_JSON;

        $response = $this->getResponse($contentType, $responseBody, $extraHeaders);

        $http = $this->createMock(ClientInterface::class);
        $http->method('request')->will($this->returnCallback(function ($method, $address, array $options) use ($response) {
            $this->lastRequestOptions = $options;

            return $response;
        }));

        return $http;
    }

    abstract public function newClient(?ClientInterface $http = null);

    abstract public function errorResponsesProvider(): iterable;

    abstract public function loadFixture($filename): string;

    /**
     * @dataProvider errorResponsesProvider
     */
    public function test_client_can_handle_error_response(string $fixtureFileName, int $statusCode, array $errors, ?string $contentType = null)
    {
        $client = $this->newClient($http = $this->getHttpClient());
        $client->setLogger($logger = new TestLogger());

        $responseMock = $this->getResponse($contentType ?? 'application/json', $this->loadFixture($fixtureFileName));
        $responseMock->method('getStatusCode')->willReturn($statusCode);

        $http->method('request')->will($this->returnCallback(function () use ($responseMock) {
            throw new ServerException('', $this->createMock(RequestInterface::class), $responseMock);
        }));

        $response = $client->sendRequest($this->createMock(Request::class));
        $this->assertTrue($logger->hasDebugRecords());

        $this->assertSame(4, $this->countRecordsWithLevel($logger, LogLevel::DEBUG));
        $this->assertTrue($logger->hasDebugThatContains('API responded with an HTTP error code'));

        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'exception'));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'error_code'));

        foreach ($logger->records as $record) {
            if (\array_key_exists('error_code', $record['context'])) {
                $this->assertSame((string) $statusCode, $record['context']['error_code']);
            }
        }

        $this->assertInstanceOf(Response::class, $response);

        $this->assertCount(\count($errors), take($response->getMessages()));

        $this->assertCount(\count($errors), zip($errors, $response->getMessages())
            ->unpack(function (array $expected, HasErrorCode $message) {
                $this->assertSame($expected[0], $message->getErrorCode());
                $this->assertSame($expected[1], $message->getMessage());
            })
        );
    }

    protected function countRecordsWithLevel(TestLogger $logger, string $level): int
    {
        return \count($logger->recordsByLevel[$level]);
    }

    protected function countRecordsWithContextKey(TestLogger $logger, string $key): int
    {
        $count = 0;

        foreach ($logger->records as $record) {
            if (\array_key_exists($key, $record['context'])) {
                ++$count;
            }
        }

        return $count;
    }
}

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

use CommonSDK\Contracts\Request;
use CommonSDK\Contracts\Response;
use CommonSDK\Tests\Common\ClientTestCase;
use CommonSDK\Types\FileResponse;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use JSONSerializer\Serializer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;
use Tests\CommonSDK\Types\Fixtures\ExampleJsonParamRequest;
use Tests\CommonSDK\Types\Fixtures\ExampleJsonRequest;
use Tests\CommonSDK\Types\Fixtures\ExampleParamRequest;
use Tests\CommonSDK\Types\Fixtures\ExampleResponse;
use Tests\CommonSDK\Types\Fixtures\TestClient;

/**
 * @covers \CommonSDK\Types\Client
 */
class ClientTest extends ClientTestCase
{
    public function newClient(?ClientInterface $http = null): TestClient
    {
        $http = $http ?? $this->createMock(ClientInterface::class);

        return new TestClient($http, new Serializer());
    }

    public function test_client_can_handle_any_request()
    {
        $client = $this->newClient($this->getHttpClient('text/plain', 'example'));
        $response = $client->sendAnyRequest($this->createMock(Request::class));
        $this->assertInstanceOf(ResponseInterface::class, $response);

        $this->assertEmpty($this->lastRequestOptions);
    }

    public function test_client_can_handle_attachments()
    {
        $client = $this->newClient($this->getHttpClient('application/pdf', '%PDF', [
            'Content-Disposition' => 'attachment; filename=testing123.pdf',
        ]));
        $response = $client->sendRequest($this->createMock(Request::class));
        $this->assertInstanceOf(FileResponse::class, $response);

        \assert($response instanceof FileResponse);

        $this->assertSame('%PDF', (string) $response->getBody());
        $this->assertEmpty($this->lastRequestOptions);
    }

    public function test_client_can_handle_binary_responses()
    {
        $client = $this->newClient($this->getHttpClient('application/octet-stream', '%PDF'));
        $response = $client->sendRequest($this->createMock(Request::class));
        $this->assertInstanceOf(FileResponse::class, $response);

        \assert($response instanceof FileResponse);

        $this->assertSame('%PDF', (string) $response->getBody());
        $this->assertEmpty($this->lastRequestOptions);
    }

    public function test_client_can_log_param_request()
    {
        $client = $this->newClient($this->getHttpClient());
        $client->setLogger($logger = new TestLogger());

        $request = new ExampleParamRequest();
        $response = $client->sendExampleParamRequest($request);

        /** @var $response ExampleResponse */
        $this->assertInstanceOf(ExampleResponse::class, $response);

        $this->assertSame(3, $this->countRecordsWithLevel($logger, LogLevel::DEBUG));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'content-type'));

        $this->assertTrue($logger->hasDebugThatContains(self::DEFAULT_JSON));

        $this->assertTrue($logger->hasDebug([
            'message' => '{method} {location}',
            'context' => [
                'method'       => 'GET',
                'location'     => '/example/request',
            ],
        ]));

        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'method'));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'location'));
    }

    public function test_client_can_log_json_response()
    {
        $client = $this->newClient($this->getHttpClient());
        $client->setLogger($logger = new TestLogger());

        $request = new ExampleJsonRequest();

        $response = $client->sendExampleJsonRequest($request);

        /** @var $response ExampleResponse */
        $this->assertInstanceOf(ExampleResponse::class, $response);

        $this->assertSame(4, $this->countRecordsWithLevel($logger, LogLevel::DEBUG));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'content-type'));

        $this->assertTrue($logger->hasDebugThatContains('{}'));
        $this->assertTrue($logger->hasDebugThatContains(self::DEFAULT_JSON));

        $this->assertTrue($logger->hasDebug([
            'message' => '{method} {location}',
            'context' => [
                'method'       => 'PUT',
                'location'     => '/json',
            ],
        ]));

        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'method'));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'location'));

        $this->assertSame(1, $client->postDeserializeHasBeenCalled);
        $this->assertSame(1, $client->preDeserializeHasBeenCalled);
    }

    public function test_client_can_pass_through_common_exceptions()
    {
        $client = $this->newClient($http = $this->getHttpClient());

        $http->method('request')->will($this->returnCallback(function () {
            throw new \RuntimeException();
        }));

        $this->expectException(\RuntimeException::class);
        $client->sendRequest($this->createMock(Request::class));
    }

    public function test_client_can_pass_through_exceptions_without_response()
    {
        $client = $this->newClient($http = $this->getHttpClient());

        $http->method('request')->will($this->returnCallback(function () {
            throw new RequestException('', $this->createMock(RequestInterface::class));
        }));

        $this->expectException(RequestException::class);
        $client->sendRequest($this->createMock(Request::class));
    }

    public function test_client_can_handle_unknown_error_response()
    {
        $client = $this->newClient($http = $this->getHttpClient());
        $client->setLogger($logger = new TestLogger());

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(500);
        $responseMock->method('getReasonPhrase')->willReturn('Server error');

        $http->method('request')->will($this->returnCallback(function () use ($responseMock) {
            throw new ServerException('', $this->createMock(RequestInterface::class), $responseMock);
        }));

        $response = $client->sendRequest($this->createMock(Request::class));

        $this->assertSame(2, $this->countRecordsWithLevel($logger, LogLevel::DEBUG));
        $this->assertTrue($logger->hasDebugThatContains('API responded with an HTTP error code'));

        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'exception'));
        $this->assertSame(1, $this->countRecordsWithContextKey($logger, 'error_code'));
        $this->assertSame(0, $this->countRecordsWithContextKey($logger, 'content-type'));

        $this->assertInstanceOf(Response::class, $response);

        $this->assertCount(1, $response->getMessages());
        foreach ($response->getMessages() as $message) {
            $this->assertSame('500', $message->getErrorCode());
            $this->assertSame('Server error', $message->getMessage());
        }

        $this->assertInstanceOf(ResponseInterface::class, $response);

        \assert($response instanceof ResponseInterface);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Server error', $response->getReasonPhrase());
    }

    public function errorResponsesProvider(): iterable
    {
        yield 'BadRequest' => ['BadRequest.json', 400, [
            ['ERROR', 'Bad Request'],
        ]];

        yield 'Alternative Content-Type' => ['Content-Type.json', 401, [
            ['ERROR', 'Bad Request'],
        ], 'text/x-json'];
    }

    public function test_fails_on_unknown_method()
    {
        $this->expectException(\BadMethodCallException::class);

        $invalid = 'invalid';
        $this->newClient()->{$invalid}();
    }

    public function possibleRequests()
    {
        yield 'GET' => [new ExampleParamRequest('GET'), 'query'];
        yield 'POST' => [new ExampleParamRequest('POST'), 'form_params'];
        yield 'PATCH' => [new ExampleJsonRequest(), 'body', 'application/json'];
        yield 'PUT' => [new ExampleJsonParamRequest(), 'query', 'application/json'];
    }

    /**
     * @param ExampleParamRequest|ExampleJsonRequest $request
     *
     * @dataProvider possibleRequests
     */
    public function test_request(Request $request, string $expectedOptionsKey, ?string $contentType = null)
    {
        $client = $this->newClient($this->getHttpClient());
        $client->sendRequest($request);

        $this->assertArrayHasKey($expectedOptionsKey, $this->lastRequestOptions);

        $this->assertSame(
            $request::EXPECTED,
            $this->lastRequestOptions[$expectedOptionsKey]
        );

        $this->assertSame(
            $contentType,
            @$this->lastRequestOptions['headers']['Content-Type']
        );
    }

    public function loadFixture($filename): string
    {
        return '{"code":"ERROR","message":"Bad Request"}';
    }
}

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

use CommonSDK\Contracts\Client as ClientContract;
use CommonSDK\Contracts\JsonRequest;
use CommonSDK\Contracts\ParamRequest;
use CommonSDK\Contracts\Request;
use CommonSDK\Contracts\Response;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use JMS\Serializer\SerializerInterface;
use JSONSerializer\Serializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Common JSON API SDK Client.
 */
abstract class Client implements ClientContract
{
    use LoggerAwareTrait;

    protected const ERROR_CODE_RESPONSE_CLASS_MAP = [
        // \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST => ErrorResponse::class, // 400
        // \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN   => OtherErrorResponse::class, // 403
    ];

    /** @var ClientInterface */
    private $http;

    /** @var SerializerInterface|Serializer */
    private $serializer;

    /** @var LoggerInterface|null */
    protected $logger;

    public function __construct(ClientInterface $http, SerializerInterface $serializer)
    {
        $this->http = $http;
        $this->serializer = $serializer;
    }

    /**
     * @see \CommonSDK\Contracts\Client::sendRequest()
     *
     * @return Response
     */
    public function sendRequest(Request $request)
    {
        try {
            $response = $this->http->request(
                $request->getMethod(),
                $request->getAddress(),
                $this->extractOptions($request)
            );
        } catch (BadResponseException $exception) {
            if ($this->logger) {
                $this->logger->debug('API responded with an HTTP error code {error_code}', [
                    'exception'  => $exception,
                    'error_code' => (string) $exception->getCode(),
                ]);
            }

            $response = $exception->getResponse();

            if ($badResponse = $this->deserializeBadResponse($response)) {
                return $badResponse;
            }

            // Handling $response in common handler below.
        }

        return $this->deserialize($request, $response);
    }

    /** @phan-suppress PhanDeprecatedFunction */
    public function __call(string $name, array $arguments)
    {
        if (0 === \strpos($name, 'send')) {
            /** @psalm-suppress MixedArgument */
            return $this->sendRequest(...$arguments);
        }

        throw new \BadMethodCallException(\sprintf('Method [%s] not found in [%s].', $name, __CLASS__));
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress MoreSpecificReturnType
     */
    private function deserialize(Request $request, ResponseInterface $response): Response
    {
        return $this->deserializeResponse(
            $response,
            $request->getResponseClassName(),
            $request->getSerializationFormat()
        );
    }

    private function deserializeBadResponse(ResponseInterface $response): ?Response
    {
        if (!\array_key_exists($response->getStatusCode(), static::ERROR_CODE_RESPONSE_CLASS_MAP)) {
            return null;
        }

        return $this->deserializeResponse(
            $response,
            static::ERROR_CODE_RESPONSE_CLASS_MAP[$response->getStatusCode()],
            Request::SERIALIZATION_JSON
        );
    }

    /**
     * @psalm-suppress MissingReturnType
     *
     * @param class-string $responseClassName
     *
     * @throws \TypeError
     */
    private function deserializeResponse(ResponseInterface $response, string $responseClassName, string $serializationFormat)
    {
        $contentType = $this->getContentTypeHeader($response);

        if ($this->logger && $contentType !== null) {
            $this->logger->debug('Content-Type: {content-type}', [
                'content-type' => $contentType,
            ]);
        }

        if (!$this->isTextResponse($contentType ?? '')) {
            if ($this->hasAttachment($response, $contentType)) {
                return new FileResponse($response->getBody());
            }

            return HTTPErrorResponse::withHTTPResponse($response);
        }

        $responseBody = (string) $response->getBody();

        if ($this->logger) {
            $this->logger->debug($responseBody);
        }

        return $this->serializer->deserialize($responseBody, $responseClassName, $serializationFormat);
    }

    private function serialize(Request $request): string
    {
        return $this->serializer->serialize($request, Request::SERIALIZATION_JSON);
    }

    private function hasAttachment(ResponseInterface $response, ?string $contentType): bool
    {
        if (self::OCTET_STREAM_TYPE === $contentType) {
            return true;
        }

        if (!$response->hasHeader(self::CONTENT_DISPOSITION)) {
            return false;
        }

        return 0 === \strpos(
            $response->getHeader(self::CONTENT_DISPOSITION)[0],
            self::CONTENT_DISPOSITION_ATTACHEMENT,
        );
    }

    private function getContentTypeHeader(ResponseInterface $response): ?string
    {
        if ($response->hasHeader(self::CONTENT_TYPE)) {
            return $response->getHeader(self::CONTENT_TYPE)[0];
        }

        return null;
    }

    protected function isTextResponse(string $header): bool
    {
        return 0 === \strpos($header, self::JSON_CONTENT_TYPE);
    }

    private function extractOptions(Request $request): array
    {
        if ($this->logger) {
            $this->logger->debug('{method} {location}', [
                'method'   => $request->getMethod(),
                'location' => $request->getAddress(),
            ]);
        }

        if ($request instanceof JsonRequest) {
            $requestBody = $this->serialize($request);

            if ($this->logger) {
                $this->logger->debug($requestBody);
            }

            $options = [
                RequestOptions::BODY    => $requestBody,
                RequestOptions::HEADERS => [
                    self::CONTENT_TYPE => self::JSON_CONTENT_TYPE,
                ],
            ];

            if ($request instanceof ParamRequest) {
                $options[RequestOptions::QUERY] = $request->getParams();
            }

            return $options;
        }

        if ($request instanceof ParamRequest) {
            if ($request->getMethod() === 'GET') {
                return [
                    RequestOptions::QUERY => $request->getParams(),
                ];
            }

            return [
                RequestOptions::FORM_PARAMS => $request->getParams(),
            ];
        }

        return [];
    }

    private const CONTENT_DISPOSITION_ATTACHEMENT = 'attachment';
    private const JSON_CONTENT_TYPE = 'application/json';
    private const OCTET_STREAM_TYPE = 'application/octet-stream';
    private const CONTENT_TYPE = 'Content-Type';
    private const CONTENT_DISPOSITION = 'Content-Disposition';
}

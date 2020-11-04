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

namespace Tests\CommonSDK\Types\Fixtures;

use CommonSDK\Contracts\Response;
use CommonSDK\Types\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpCodes;

/**
 * @method ExampleResponse sendAnyRequest(Request $request);
 * @method ExampleResponse sendExampleJsonRequest(ExampleJsonRequest $request);
 * @method ExampleResponse sendExampleParamRequest(ExampleParamRequest $request);
 */
class TestClient extends Client
{
    protected const ERROR_CODE_RESPONSE_CLASS_MAP = [
        HttpCodes::HTTP_BAD_REQUEST  => ErrorResponse::class, // 400
        HttpCodes::HTTP_UNAUTHORIZED => ErrorResponse::class, // 401
    ];

    protected function isTextResponse(string $header): bool
    {
        if (0 === \strpos($header, 'text/x-json')) {
            return true;
        }

        return parent::isTextResponse($header);
    }

    /** @var int */
    public $postDeserializeHasBeenCalled = 0;

    protected function postDeserialize(ResponseInterface $httpResponse, Response $response): void
    {
        ++$this->postDeserializeHasBeenCalled;
    }

    /** @var int */
    public $preDeserializeHasBeenCalled = 0;

    protected function preDeserialize(ResponseInterface $response, string $responseClassName, ?string $contentType): void
    {
        ++$this->preDeserializeHasBeenCalled;

        parent::preDeserialize($response, $responseClassName, $contentType);
    }
}

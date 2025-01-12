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

use CommonSDK\Contracts\HasErrorCode;

use function Pipeline\take;

final class Message implements HasErrorCode
{
    /** @var string */
    private $text;

    /** @var string|null */
    private $errorCode;

    public function __construct(string $text, ?string $errorCode = null)
    {
        $this->text = $text;
        $this->errorCode = $errorCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getMessage(): string
    {
        return $this->text;
    }

    /**
     * @param iterable<HasErrorCode> ...$inputs
     *
     * @return iterable|Message[]
     */
    public static function from(...$inputs): iterable
    {
        return take($inputs)->unpack()->map(function (HasErrorCode $item) {
            if ($item->getMessage() !== '' || $item->getErrorCode() !== null) {
                yield new Message($item->getMessage(), $item->getErrorCode());
            }
        });
    }
}

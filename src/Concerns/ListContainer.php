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

namespace CommonSDK\Concerns;

use CommonSDK\Contracts\HasErrorCode;
use CommonSDK\Contracts\ItemList;

/**
 * @psalm-implements ItemList
 *
 * @see ItemList
 */
trait ListContainer
{
    private $list;

    private function __construct(array $list)
    {
        $this->list = $list;
    }

    /** @return class-string */
    public static function getListType(): string
    {
        // @phan-suppress-next-line PhanUndeclaredConstantOfClass
        return static::LIST_TYPE;
    }

    /**
     * @param array<object> $list
     *
     * @return static
     */
    public static function withList(array $list)
    {
        // @phan-suppress-next-line PhanTypeInstantiateTraitStaticOrSelf
        return new self($list);
    }

    public function hasErrors(): bool
    {
        return false;
    }

    /**
     * @return iterable|HasErrorCode[]
     *
     * @psalm-return iterable<HasErrorCode>
     */
    public function getMessages()
    {
        return [];
    }

    /**
     * @return \ArrayIterator<object>
     *
     * @psalm-return \ArrayIterator<array-key, object>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }

    public function count(): int
    {
        return \count($this->list);
    }
}

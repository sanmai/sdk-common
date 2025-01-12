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

namespace Tests\CommonSDK\Concerns;

use CommonSDK\Contracts\Property;
use PHPUnit\Framework\TestCase;
use Tests\CommonSDK\Concerns\Fixtures\ObjectProperty;

/**
 * @covers \CommonSDK\Concerns\ObjectPropertyRead
 */
class ObjectPropertyReadTest extends TestCase
{
    public function test_it_causes_notice_for_inaccessible_properties()
    {
        $this->withErrorHandler(function () {
            $instance = new ObjectProperty();

            $this->expectExceptionMessage('Undefined property: Tests\CommonSDK\Concerns\Fixtures\ObjectProperty::$private');
            $this->expectExceptionCode(E_USER_NOTICE);

            return $instance->private;
        });
    }

    public function test_it_returns_null_for_inaccessible_properties()
    {
        $this->withErrorHandler(function () {
            $instance = new ObjectProperty();

            $this->assertNull(@$instance->private);
        });
    }

    public function test_it_causes_notice_for_invalid_properties()
    {
        $this->withErrorHandler(function () {
            $instance = new ObjectProperty();

            $this->expectExceptionMessage('Undefined property: Tests\CommonSDK\Concerns\Fixtures\ObjectProperty::$invalid');
            $this->expectExceptionCode(E_WARNING);

            return $instance->invalid;
        });
    }

    public function test_it_allows_reading_property_implementing_properties()
    {
        $instance = new ObjectProperty();

        $this->assertInstanceOf(Property::class, $instance->example);
    }

    private function withErrorHandler(callable $callback): void
    {
        set_error_handler(function ($severity, $message) {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new \RuntimeException($message, $severity);
        });

        try {
            $callback();
        } finally {
            restore_error_handler();
        }
    }
}

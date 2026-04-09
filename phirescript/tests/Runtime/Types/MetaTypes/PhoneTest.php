<?php

namespace PHireScript\Tests\Runtime\Types\MetaTypes;

use PHPUnit\Framework\TestCase;
use PHireScript\Runtime\Types\MetaTypes\Phone;

class PhoneTest extends TestCase
{
    /**
     * Test that the transform method strips non-numeric characters.
     */
    public function testTransformStripsNonDigits(): void
    {
        $phone = new Phone("1-800-555-0199");
        // We check via __toString which prepends '+'
        $this->assertEquals("+18005550199", (string)$phone);

        $phoneWithSpaces = new Phone("44 20 7123 4567");
        $this->assertEquals("+442071234567", (string)$phoneWithSpaces);
    }

    /**
     * Test that validation passes for strings with 8 or more digits.
     */
    public function testValidationAcceptsValidLength(): void
    {
        // 8 digits exactly
        $phone = new Phone("12345678");
        $this->assertInstanceOf(Phone::class, $phone);
    }

    /**
     * Test that validation fails for strings with fewer than 8 digits.
     */
    public function testValidationFailsForShortInput(): void
    {
        $this->expectException(\TypeError::class);
      // "123-456" results in 6 digits after transform, which is < 8
        new Phone("123-456");
    }

    /**
     * Test the __toString magic method.
     */
    public function testToStringPrependsPlusSign(): void
    {
        $phone = new Phone("551199999999");
        $this->assertEquals("+551199999999", (string)$phone);
    }

    /**
     * Test getting the country code (first 2 characters).
     */
    public function testGetCountryCode(): void
    {
        $phone = new Phone("442071234567"); // UK
        $this->assertEquals("44", $phone->getCountryCode());

        $phoneUS = new Phone("012025550156"); // US prefix logic
        $this->assertEquals("01", $phoneUS->getCountryCode());
    }

    /**
     * Test edge case: Input that is numeric but starts with non-digits.
     */
    public function testTransformHandlesSpecialCharacters(): void
    {
        $phone = new Phone("+(12) 3456-7890");
        $this->assertEquals("+1234567890", (string)$phone);
    }
}

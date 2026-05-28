<?php

namespace Tests\Unit;

use App\Support\Platform;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    public function test_platform_aliases_are_normalized(): void
    {
        $this->assertSame('whatsapp_business', Platform::normalize('WA'));
        $this->assertSame('whatsapp_business', Platform::normalize('whatsapp'));
        $this->assertSame('whatsapp_business', Platform::normalize('WA Business'));
        $this->assertSame('instagram', Platform::normalize('IG'));
        $this->assertSame('instagram', Platform::normalize('insta'));
        $this->assertSame('telegram', Platform::normalize('TG'));
        $this->assertSame('other', Platform::normalize('unknown-platform'));
    }
}

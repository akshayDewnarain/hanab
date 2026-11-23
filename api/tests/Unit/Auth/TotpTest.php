<?php

namespace Tests\Unit\Auth;

use Domain\Auth\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_rfc_vector_and_window(): void
    {
        $totp = new Totp;
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame('287082', $totp->codeAt($secret, 1));
        $this->assertSame(1, $totp->verify($secret, '287082', 59));
        $this->assertNull($totp->verify($secret, '287082', 59 + 90));
        $this->assertNull($totp->verify($secret, '000000', 59));
    }

    public function test_provisioning_uri_stays_on_the_standard_scheme(): void
    {
        $uri = (new Totp)->provisioningUri('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'owner@hanab.test');

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $uri);
        $this->assertStringNotContainsString('http://', $uri);
    }
}

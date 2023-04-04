<?php

namespace Dienstplan\Test;

use dienstplan\Worker\Dutyroster;
use PHPUnit\Framework\TestCase;

class DutyrosterTest extends TestCase {

    public function test_find_candidate() {
        $now = new \DateTimeImmutable();
        $day =13;
        $dutyroster = new Dutyroster($now);
        // override $config

        self:assertStringContainsString(
            "anton",
            $dutyroster->find_candidate($day)
        );
    }
}

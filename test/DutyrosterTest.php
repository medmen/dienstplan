<?php
declare(strict_types=1);

namespace Dienstplan\Test;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Dienstplan\Worker\Dutyroster;

class DutyrosterTest extends TestCase {
    use ProphecyTrait;

    private $config;

    protected function setUp():void
    {
        $this->config['people'] = [
            'anton'  => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
            'berta'  => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
            'conny'  => [],
            'dick'   => [],
            'egon'   => [],
            'floppy' => [],
            'guste'  => [],
        ];

        $this->config['wishes'] = [
            'anton' => [
                "1"  => "D",
                "4"  => "F",
                "8"  => "F",
                "14" => "F",
            ],
            'berta' => [
                "3"  => "F",
                "4"  => "F",
                "8"  => "D",
                "12" => "F",
            ]
        ];

        $this->config['urlaub'] = [
            'anton' => [
                '05.02.2017 ~ 15.02.2017',
                '21.03.2017',
                '15.05.2017 ~ 25.05.2017'
            ],
            'berta' => [
                '10.02.2017 ~ 20.02.2017'
            ]
        ];

        $this->config['limits'] = [
            'total'          => 5,
            'we'             => 2,
            'fr'             => 1,
            'max_iterations' => 500
        ];
    }

    public function test_has_noduty_wish(){
        $dutyroster = $this->prophesize(Dutyroster::class);

        // override $config
        $this->assertTrue($dutyroster->has_noduty_wish('anton', 4));
    }

    public function test_find_candidate() {
        /**
         * Complex SetUp:
         * Given I have an array of name => date
         */
        $now = new \DateTimeImmutable();
        $day =13;

        $dutyroster = $this->prophesize(Dutyroster::class);

        // override $config
         $this->assertEquals('anton', $dutyroster->find_candidate($day));
    }
}

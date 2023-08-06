<?php
declare(strict_types=1);

namespace Dienstplan\Test;

use PHPUnit\Framework\TestCase;
# use Prophecy\PhpUnit\ProphecyTrait;
use Dienstplan\Worker\Dutyroster;

/**
 * Setup: extend Dutyroster class to add a setter for config data
 */
class DutyrosterMock extends Dutyroster {
    public function set_config_data(array $data):void {
        $this->config = $data;
    }
}

class DutyrosterTest extends TestCase {
  #  use ProphecyTrait;

    public function getConfig()
    {
        return [
                'people' => [
                    'anton'  => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
                    'berta'  => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
                    'conny'  => [],
                    'dick'   => [],
                    'egon'   => [],
                    'floppy' => [],
                    'guste'  => [],
                ],
                'wishes' => [
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
                ],
                'urlaub' => [
                    'anton' => [
                        '05.02.2017 ~ 15.02.2017',
                        '21.03.2017',
                        '15.05.2017 ~ 25.05.2017'
                    ],
                    'berta' => [
                        '10.02.2017 ~ 20.02.2017'
                    ]
                ],
                'limits' => [
                    'total'          => 5,
                    'we'             => 2,
                    'fr'             => 1,
                    'max_iterations' => 500
                ]
       ];
    }

    public function test_has_noduty_wish():void {
        $fixed_datetime = \DateTime::createFromFormat("m/Y", "03/2023");

        $dutyroster_mock = new DutyrosterMock($fixed_datetime);
        $dutyroster_mock->set_config_data($this->getConfig());

        $this->assertTrue($dutyroster_mock->has_noduty_wish('anton', 4));
    }
}

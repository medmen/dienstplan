<?php
declare(strict_types=1);
use \DienstplanTest\DutyrosterMock;
use Dienstplan\Worker\Wishes;
beforeEach(function () {
    $this->target_month = \DateTime::createFromFormat("m/Y", "03/2023");
    $this->wishes = new Wishes($this->target_month);
    $arr_config = [
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
                "1"  => "D",
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

    $dutyroster_mock = new DutyrosterMock($this->target_month);
    $dutyroster_mock->set_config_data($arr_config);
});
test('load wishes', function () {
    $expectedWishes = [
        'anton' => [
            "1"  => "D",
            "4"  => "F",
        ],
        'berta' => [
            "1"  => "D",
            "3"  => "F",
        ]
    ];

    $existingWishes = $this->wishes->load_wishes();
    expect($existingWishes)->toContain($expectedWishes);
});
test('load wishes with missing files', function () {
    $expectedWishes = [
        'anton' => [],
        'berta' => []
    ];

    // Modify conffiles to point to non-existent files
    $this->wishes = $this->createPartialMock(Wishes::class, ['__construct']);
    $this->wishes->method('__construct')->willReturn(null);
    $this->wishes->conffiles = [
        'people' => 'path/to/nonexistent/people.php',
        'wishes' => 'path/to/nonexistent/wishes.php'
    ];

    expect($this->wishes->load_wishes())->toEqual($expectedWishes);
});
/**
    * public function testLoadWishesWithMissingPeople()
    * {
        * $expectedWishes = [
            * 'John' => [],
            * 'Jane' => []
        * ];
 *
* // Modify config to have no people
        * $this->wishes = $this->createPartialMock(Wishes::class, ['__construct']);
        * $this->wishes->method('__construct')->willReturn(null);
        * $this->wishes->config = [
            * 'people' => []
        * ];
 *
* // Modify conffiles to point to existing files
        * $this->wishes->conffiles = [
            * 'people' => 'path/to/existing/people.php',
            * 'wishes' => 'path/to/existing/wishes.php'
        * ];
 *
* $this->assertEquals($expectedWishes, $this->wishes->load_wishes());
* }
**/


<?php

declare(strict_types=1);

use Dienstplan\Worker\Dutyroster;
use Odan\Session\MemorySession;
use Dienstplan\Worker\People;
use Dienstplan\Worker\Wishes;

beforeEach(closure: function () {
    // parent::setUp(); // TODO: Change the autogenerated stub
    $this->target_month = \DateTimeImmutable::createFromFormat("m/Y", "03/2023");
    $this->session = new MemorySession();
    $this->config = [
        'people' => [
            //pw is create using password_hash("my super secret password", PASSWORD_DEFAULT);
            'anton'  => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'],
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

    $wishes = Mockery::mock(Wishes::class);
    $wishes->shouldReceive('get_wishes_for_month')
        ->with($this->target_month)
        ->andReturn($this->config['wishes']);

    $people = Mockery::mock(People::class);
    $people->shouldReceive('load')
        ->with($this->target_month)
        ->andReturn($this->config['people']);

    $people->shouldReceive('load_for_month')
        ->with($this->target_month)
        ->andReturn($this->config['people']);

    $this->dutyroster = new Dutyroster( $this->session, $people, $wishes);

});

afterEach(function () {
});



test('create for month', function() {
    $result = $this->dutyroster->create_or_show_for_month($this->target_month);

    expect($result)->toBeArray();
    // ->toContain(1);
}) ;

test('has_noduty_wish', function (string $candidate, int $day) {
    $this->dutyroster->set_people_and_wishes_for_month($this->target_month);
    $candidateForDay = $this->dutyroster->has_noduty_wish($candidate, $day);
    expect($candidateForDay)->toBeTrue();
})->with([
    ['anton', 4],
    ['anton', 8],
    ['berta', 3],
    ['berta', 4],
    ['berta', 12]
]);

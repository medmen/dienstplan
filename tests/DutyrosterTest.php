<?php

declare(strict_types=1);

use \DienstplanTest\DutyrosterMock;
use Odan\Session\MemorySession;

beforeEach(closure: function () {
    // parent::setUp(); // TODO: Change the autogenerated stub
    $this->target_month = \DateTime::createFromFormat("m/Y", "03/2023");
    $this->session = new MemorySession();
    $this->config = [
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

    $this->dutyroster_mock = new DutyrosterMock($this->session, $this->target_month);
    $this->dutyroster_mock->set_config_data($this->config);

});
afterEach(function () {
});

test('get wishes', function() {
    expect($this->dutyroster_mock->get_wishes_for_month($session, $target_month))->toBeArray();
}) ;

test('has noduty wish', function () {
    expect($this->dutyroster_mock->has_noduty_wish('anton', 4))->toBeTrue();
})->depends('get wishes');

test('candidates have duty wish', function () {
    expect($this->dutyroster_mock->candidates_have_duty_wish(1))->toContain('anton');
    expect($this->dutyroster_mock->candidates_have_duty_wish(8))->toContain('berta');
})->depends('get wishes');

test('had duty previous day', function () {
    $this->dutyroster_mock->set_dienstplan(['1' => 'anton', '2' => 'berta']);

    expect($this->dutyroster_mock->had_duty_previous_day('berta', 3))->toBeTrue();
    expect($this->dutyroster_mock->had_duty_previous_day('anton', 3))->toBeFalse();
    expect($this->dutyroster_mock->had_duty_previous_day('conny', 3))->toBeFalse();
});
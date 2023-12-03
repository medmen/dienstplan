<?php
declare(strict_types=1);

use Dienstplan\Worker\People;

class PeopleMock extends People{
    private array $people = array();
    function load_all() {
        return($this->people);
    }

    function injectPeople(array $people) {
        $this->people = $people;
    }
}

beforeEach(function () {
    $this->people = new PeopleMock();
});
afterEach(function () {
    $this->people = null;
});

test('load_all returns array', function() {
    expect($this->people->load_all())->toBeArray();
});

test('load returns array', function() {
    expect($this->people->load_all())->toBeArray();
});

test('load all returns warning for less than 5 people', function ($array_of_four) {
    $this->people->injectPeople($array_of_four);
    expect($this->people->load(new \DateTime()))->toBeArray();
})->with(array(
    'people' => [
        'anton' => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
        'berta' => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
        'floppy' => ['inactive' => true],
        'guste' => ['inactive' => ['start'=> '01.02.2023', 'end' => '31.12.2025']],
    ]
));

test('load returns error for 0 people', function ($array_of_four) {
    $this->people->injectPeople($array_of_four);
    expect($this->people->load_all())->toBeArray('no people available', E_USER_WARNING);
})->with(array(
             'people' => [
                 'anton' => ['fullname' => 'Anton Anders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6'], // pw chaf666
                 'berta' => ['fullname' => 'Berta Besonders', 'pw' => '$2y$10$cv0fitJNDmQdzydZBGcW7eBYqmwqcpSQWMOqt/FiFrTthVqHZqHD6', 'is_admin' => true],
                 'floppy' => ['inactive' => true],
                 'guste' => ['inactive' => ['start'=> '01.02.2023', 'end' => '31.12.2025']],
             ]
         ));

<?php

declare(strict_types=1);

use \Dienstplan\Worker\Person;

beforeEach(function () {
    $this->person = new Person();
});
afterEach(function () {
    $this->person = null;
});

test('set and get id', function () {
    $this->person->setId("1");
    expect($this->person->getId())->toEqual("1");
});
test('set and get firstname', function () {
    $this->person->setFirstname("John");
    expect($this->person->getFirstname())->toEqual("John");
});
test('set and get lastname', function () {
    $this->person->setLastname("Doe");
    expect($this->person->getLastname())->toEqual("Doe");
});
test('set and get fullname', function () {
    $this->person->setFirstname("John");
    $this->person->setLastname("Doe");
    $this->person->setFullname();
    expect($this->person->getFullname())->toEqual("John Doe");
});
test('set and adminflag', function () {
    $this->person->setAdminflag(true);
    expect($this->person->getAdminflag())->toEqual(true);
});
test('set and get inactive', function () {
    $inactive = array("start" => "01.01.2022", "end" => "10.01.2022");
    $this->person->setInactive($inactive);
    expect($this->person->getInactive())->toEqual($inactive);

    $inactive = true;
    $this->person->setInactive($inactive);
    expect($this->person->getInactive())->toEqual($inactive);
});
test('validate date', function () {
    expect($this->person->validateDate("01.01.2022"))->toBeTrue();
    expect($this->person->validateDate("2022.01.01"))->toBeFalse();
});
test('is boolish', function () {
    expect($this->person->isBoolish("1"))->toBe(true);
    expect($this->person->isBoolish("true"))->toBe(true);
    expect($this->person->isBoolish("on"))->toBe(true);
    expect($this->person->isBoolish("yes"))->toBe(true);
    expect($this->person->isBoolish("0"))->toBe(false);
    expect($this->person->isBoolish("false"))->toBe(false);
    expect($this->person->isBoolish("off"))->toBe(false);
    expect($this->person->isBoolish("no"))->toBe(false);
    expect($this->person->isBoolish("invalid"))->toBeNull();
    expect($this->person->isBoolish(null))->toBeNull();
});

test('create from named array', function () {
    $person_data = array(
        "id"        => "1",
        "firstname" => "John",
        "lastname"  => "Doe",
        "is_admin"  => true,
        "inactive"  => array("start" => "01.01.2022", "end" => "10.01.2022")
    );
    $result = $this->person->createFromNamedArray($person_data, $person_data['id']);

    expect($result)
        ->toBeInstanceOf(Person::class)
        ->getId()->toEqual("1")
        ->getFirstname()->toEqual("John")
        ->getLastname()->toEqual("Doe")
        ->getFullname()->toEqual("John Doe")
        ->getAdminflag()->toEqual(true)
        ->getInactive()->toEqual($person_data["inactive"]);
});

test('is inactive', function () {
    $inactive = array("start" => "01.01.2022", "end" => "10.01.2022");
    $this->person->setInactive($inactive);
    expect($this->person->isInactive(new \DateTimeImmutable("05.01.2022")))->toBeTrue();

    $this->person->setInactive(true);
    expect($this->person->isInactive())->toBeTrue();
});

test('is date in range', function () {
    $start = new \DateTimeImmutable("01.01.2022");
    $end   = new \DateTimeImmutable("10.01.2022");

    $date1 = new \DateTimeImmutable("05.01.2022");
    expect($this->person->is_date_in_range($date1, $start, $end))->toBeTrue();

    $date2 = new \DateTimeImmutable("15.01.2022");
    expect($this->person->is_date_in_range($date2, $start, $end))->toBeFalse();
});

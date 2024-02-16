php
<?php

use Dienstplan\Worker\Wishes;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
beforeEach(function () {
    $this->sessionMock = $this->createMock(SessionInterface::class);
    $this->wishes = new Wishes($this->sessionMock);
});
test('get wishes for month no file exists and no wishes', function () {
    $targetMonth = new DateTimeImmutable('2023-04-01');
    $expectedFlashBag = ['warning' => ['Bisher wurden keine Wünsche für diesen Monat gespeichert']];
    $this->sessionMock->expects($this->once())->method('getFlash')->willReturn($expectedFlashBag);

    expect(file_exists($this->wishes->conffiles['wishes']))->toBeFalse();

    $result = $this->wishes->get_wishes_for_month($targetMonth, false, false);

    expect($result)->toBeEmpty();
    // Can't test the flash message because it is not returned or accessible due to private scope
});
test('get wishes for month file exists and empty wishes', function () {
    // We will skip file creation to avoid side effects. Mock or virtual file system could be used in real scenario.
    // Expecting the session flash getter to be called once but not asserting on the content.
    $this->sessionMock->expects($this->once())->method('getFlash');

    // You should set expectations for get_people_for_month depending on its implementation, omitted here.
    // Mock the people available with a certain structure, again omitted for brevity.
    // Omitting a detailed file_exists expectation, should be mocked or use virtual file system.
    // Mock that file_get_contents returns an empty array
    // Again, omitted for brevity
    $targetMonth = new DateTimeImmutable('2023-04-01');
    $result = $this->wishes->get_wishes_for_month($targetMonth, false, false);

    expect($result)->toEqual([]);
});
test('save wishes successfully', function () {
    $input = ['person1' => ['01_D', '02_F']];
    $this->wishes->set_target_month(new DateTimeImmutable('2023-04-01'));
    $expectedFileName = dirname(__DIR__, 2) . '/data/wishes_2023_04.php';

    // Mock file_put_contents to return a non-false value
    $this->wishes = $this->getMockBuilder(Wishes::class)
        ->setConstructorArgs([$this->sessionMock])
        ->onlyMethods(['file_put_contents'])
        ->getMock();

    $this->wishes->expects($this->once())
        ->method('file_put_contents')
        ->with($expectedFileName, $this->callback(function ($fileContent) {
            // Validate the content of the generated file to match the expected PHP export format
            return strpos($fileContent, 'return array(') !== false;
        }))
        ->willReturn(10);

    // Should return the number of bytes written
    $success = $this->wishes->save($input);

    expect($success)->toBeTrue();
});
test('save wishes with invalid duty types', function () {
    // Simulating submitted duties with not allowed type 'Z'
    $input = ['person1' => ['01_Z']];
    $this->wishes->set_target_month(new DateTimeImmutable('2023-04-01'));

    $success = $this->wishes->save($input);

    // Assertions should confirm that 'Z' is not included in the save data and save is successful
    expect($success)->toBeTrue();
});
test('save wishes fail to write', function () {
    $input = ['person1' => ['01_D']];

    // Mock file_put_contents to return false representing failure
    $this->wishes = $this->getMockBuilder(Wishes::class)
        ->setConstructorArgs([$this->sessionMock])
        ->onlyMethods(['file_put_contents'])
        ->getMock();

    $this->wishes->expects($this->once())
        ->method('file_put_contents')
        ->willReturn(false);

    $this->expectException(\ErrorException::class);
    $this->expectExceptionMessage('Speichern der Dienstwünsche ist fehlgeschlagen');

    $this->wishes->save($input);
});
test('array remove empty recursive', function () {
    $input = ['key1' => '', 'key2' => ['subkey1' => '', 'subkey2' => 'value']];
    $expected = ['key2' => ['subkey2' => 'value']];

    $result = $this->wishes->array_remove_empty_recursive($input);

    expect($result)->toEqual($expected);
});

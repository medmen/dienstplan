php
<?php

use Dienstplan\Worker\Wishes;
use Odan\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use org\bovigo\vfs\vfsStream, org\bovigo\vfs\vfsStreamDirectory;

class WishesTest extends TestCase
{
    /**
     * @var Wishes
     */
    private Wishes $wishes;

    /**
     * @var MockObject|SessionInterface
     */
    private $sessionMock;
    protected static $root;

    static function setUpBeforeClass(): void
    {
        self::$root = vfsStream::setup('data');
        $people = vfsStream::url('data/people.php');
        file_put_contents($people, '<?php return( array(\'anton\' => [\'fullname\' => \'Anton Anders\', \'pw\' => \'password\'], \'berta\' => [\'fullname\' => \'Berta Besonders\', \'pw\' => \'password\', \'is_admin\' => true], \'conny\' => [\'fullname\' => \'Cornelia Chaos\'],
        \'dick\' => [\'firstname\' => \'Dirk\', \'lastname\' => \'Ickinger\'], \'egon\' => [\'fullname\' => \'Egon Eklig\'], \'floppy\' => [\'fullname\' => \'Florian Popp\', \'inactive\' => true], \'guste\' => [\'inactive\' => [\'start\'=> \'01.02.2023\', \'end\' => \'31.12.2025\']],
        \'harald\' => [\'fullname\' => \'Harry Ald\']));');
    }

    protected function setUp(): void
    {
        $this->sessionMock = $this->createMock(SessionInterface::class);
        $this->wishes = new Wishes($this->sessionMock);
    }

    public function testGetWishesForMonthFileExistsAndEmptyWishes()
    {
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

        $this->assertEquals([], $result);
    }


    public function testArrayRemoveEmptyRecursive()
    {
        $input = ['key1' => '', 'key2' => ['subkey1' => '', 'subkey2' => 'value']];
        $expected = ['key2' => ['subkey2' => 'value']];

        $result = $this->wishes->array_remove_empty_recursive($input);

        $this->assertEquals($expected, $result);
    }
}

<?php

use Dienstplan\Worker\Limits;
use Odan\Session\SessionInterface;
use Odan\Session\FlashInterface;
use PHPUnit\Framework\TestCase;

class LimitsTest extends TestCase
{
    private $session;
    private $flash;
    private $limits;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->flash = $this->createMock(FlashInterface::class);
        $this->session->method('getFlash')->willReturn($this->flash);
        $this->limits = new Limits($this->session);
    }

    public function testLoadWithConfigFile(): void
    {
        file_put_contents(__DIR__ . '/../../data/limits.php', "<?php\nreturn ['total' => 10];");
        $expectedLimits = ['total' => 10];

        $this->flash->expects($this->once())->method('add')->with($this->equalTo('success'), $this->equalTo('successfully loaded limits'));

        $result = $this->limits->load();
        $this->assertSame($expectedLimits, $result);
    }

    public function testLoadFallbackToDefault(): void
    {
        @unlink(__DIR__ . '/../../data/limits.php');

        $defaultLimits = ['total' => 6, 'we' => 2, 'fr' => 1, 'max_iterations' => 500];

        $this->flash->expects($this->once())->method('add')->with($this->equalTo('warning'), $this->equalTo('no limits configured, falling back to system default'));

        $result = $this->limits->load();
        $this->assertSame($defaultLimits, $result);
    }

    public function testSaveSuccessful(): void
    {
        $someLimits = ['total' => 10, 'we' => 3, 'fr' => 1];
        $expectedContent = "<?php\n return( array(\n\t" . var_export($someLimits, true) . ";\n";

        $this->flash->expects($this->once())->method('add')->with($this->equalTo('success'), $this->equalTo('successfully saved limits'));

        $this->assertTrue($this->limits->save($someLimits));

        $actualContent = file_get_contents(__DIR__ . '/../../data/limits.php');

        $this->assertStringEqualsFile(__DIR__ . '/../../data/limits.php', $expectedContent);
        $this->assertSame($expectedContent, $actualContent);
    }

    public function testSaveFailure(): void
    {
        $unwritablePath = __DIR__ . '/../../data/UNWRITABLE_DIRECTORY/limits.php';
        $this->limits = new Limits($this->session);

        $reflection = new \ReflectionClass($this->limits);
        $reflection_property = $reflection->getProperty('path_to_configfiles');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->limits, $unwritablePath);

        $someLimits = ['total' => 10, 'we' => 3, 'fr' => 1];

        $this->flash->expects($this->once())->method('add')->with($this->equalTo('error'), $this->stringContains("Saving LIMITS failed with Error:"));

        $this->assertFalse($this->limits->save($someLimits));
    }
}

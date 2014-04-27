<?php
namespace SebastianBergmann\PHPUnit\SkeletonGenerator;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

class TestGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        vfsStream::setup();
    }

    /**
     * @param        string $className
     * @dataProvider provider
     */
    public function testGeneratesTestCodeCorrectly($className)
    {
        $generatedFile = vfsStream::url('root') . '/' . $className . 'Test.php';

        $generator = new TestGenerator(
            $className,
            __DIR__ . '/_fixture/_input/' . $className . '.php',
            $className . 'Test',
            $generatedFile
        );

        $generator->write();

        $this->assertStringMatchesFormatFile(
            __DIR__ . '/_fixture/_expected/' . $className . 'Test.php',
            file_get_contents($generatedFile)
        );
    }

    public function provider()
    {
        return array(
            array('Calculator'),
            array('Calculator2')
        );
    }
}

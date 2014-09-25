<?php
namespace SebastianBergmann\PHPUnit\SkeletonGenerator;

use PHPUnit_Framework_TestCase;
use org\bovigo\vfs\vfsStream;

class TestsGeneratorTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        vfsStream::setup();
    }

    /**
     * @param        string $inFolder
     *
     * @dataProvider provider
     */
    public function testGeneratesTestCodeCorrectly($inFolder)
    {
        $outFolder = vfsStream::url('root');

        $generator  = new TestsGenerator();
        $generators = $generator->getGenerators($inFolder, $outFolder, '{classname}Test');

        foreach($generators as $testGenerator)
        {
            $testGenerator->write();

            $expectedSourceFile = __DIR__ . '/_fixture/_expected/' . (basename($testGenerator->getInSourceFile(), ".php")) . 'Test.php';
            if(file_exists($expectedSourceFile))
            {
                $this->assertStringMatchesFormatFile($expectedSourceFile, file_get_contents($testGenerator->getOutSourceFile()));
            }
        }
    }

    public function provider()
    {
        return [[__DIR__ . '/_fixture/_input']];
    }
}

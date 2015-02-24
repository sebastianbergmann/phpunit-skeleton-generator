<?php
/**
 * phpunit-skeleton-generator
 *
 *
 * @author    Curtis Kelsey <curtis.kelsey@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 */
namespace SebastianBergmann\PHPUnit\SkeletonGenerator\CLI;

use SebastianBergmann\PHPUnit\SkeletonGenerator\AbstractGenerator;
use SebastianBergmann\PHPUnit\SkeletonGenerator\TestGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
 
Class GenerateModuleTestsCommand extends Command
{
    private $root;
    
    private $moduleName;
    
    private $sourceCodePath;
    
    private $testCodePath;
    
    private $verbose = false;
    
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->addOption(
            'bootstrap',
            null,
            InputOption::VALUE_REQUIRED,
            'A "bootstrap" PHP file that is run at startup'
        );
        
        $this->setName('generate-module-tests')
             ->setDescription('Generates all tests within a module')
             ->addArgument(
                 'module-path',
                 InputArgument::OPTIONAL,
                 'The root path of the module to scan'
             )
             ->addArgument(
                 'source-path',
                 InputArgument::OPTIONAL,
                 'The directory that the source code is stored in'
             )
             ->addArgument(
                 'test-path',
                 InputArgument::OPTIONAL,
                 'The directory that the test code is stored in'
             );

        parent::configure();
    }
    
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('bootstrap') && file_exists($input->getOption('bootstrap'))) {
            
            include $input->getOption('bootstrap');
        }
        
        if ($input->getArgument('module-path')) {

            if (substr($input->getArgument('module-path'), 0, 1) === '/') {

                $this->root = rtrim($input->getArgument('module-path'),'/');

            } else {

                $this->root = getcwd().'/'.rtrim($input->getArgument('module-path'),'/');
            }

            
        } else {
            // Default root path
            $this->root = getcwd();
        }
        
        if ($output->isVerbose()) {
            
            echo "Current Working Directory: ".$this->root."\n";
        }
        
        $this->moduleName = basename($this->root);
        
        if ($output->isVerbose()) {
            
            echo "Current Module's Name: ".$this->moduleName."\n";
        }
        
        if ($input->getArgument('source-path')) {
            
            $this->sourceCodePath = rtrim($input->getArgument('source-path'),'/');
            
        } else {
            // Default source path
            $this->sourceCodePath = $this->root."/src/".$this->moduleName;
        }
        
        if ($output->isVerbose()) {
            
            echo "Source Code Path: ".$this->sourceCodePath."\n";
        }
        
        if ($input->getArgument('test-path')) {
            
            $this->testCodePath = rtrim($input->getArgument('test-path'),'/');
            
        } else {
            // Default test path
            $this->testCodePath = $this->root."/tests/".$this->moduleName."Test";
        }
        
        if ($output->isVerbose()) {
            
            echo "Test Code Path: ".$this->testCodePath."\n";
        }

        // Check for a source code directory to generate tests from
        if (!file_exists($this->sourceCodePath)) {
            
            echo "There is not a source code directory located at $this->sourceCodePath. Are you sure you are in the 
                    root of a module?";
            return;    
        }

        if (!file_exists($this->root.'/tests')) {

            mkdir($this->root.'/tests');
        }
        
        // Check for the test directory we will store tests in
        if (!file_exists($this->testCodePath)) {
            
            mkdir($this->testCodePath);
        }
        
        $this->descendDirectory($this->sourceCodePath, $output);
    }
    
    private function descendDirectory($directory, &$output)
    {
        if ($output->isVerbose()) {
            
            echo "Scanning $directory...\n";
        }
        
        // If it is not a directory stop here
        if (!is_dir($directory)) {
            
            return; 
        }
        
        //Grab the relative path
        $relativePath = substr($directory, strlen($this->sourceCodePath));
        $namespaceRelativePath = str_replace("/", "\\", $relativePath);

        if (!file_exists($this->testCodePath.$relativePath)) {

            mkdir($this->testCodePath.$relativePath);
        }
        
        // Grab all of the directory's children
        $children = scandir($directory);
        
        foreach ($children as $child) {

            // Don't traverse the pointers
            if ($child=='.' || $child=='..') {
                
                continue;
            }
            
            // If the child is a directory
            if (is_dir($directory.'/'.$child)) {
                
                // And does not exist in the test code path
                if (!file_exists($this->testCodePath.$relativePath.'/'.$child)) {
                    
                    // Create it
                    if ($output->isVerbose()) {
                        
                        echo "Creating $relativePath/$child in the test code path...";
                    }
                    
                    mkdir($this->testCodePath.$relativePath.'/'.$child);
                }  
                
                $this->descendDirectory($directory.'/'.$child, $output);
            }  
            
            // If the child is a file
            if (is_file($directory.'/'.$child)) {
                
                $nameParts = explode(".",$child);
                $filename = $nameParts[0].'Test.'.$nameParts[1];
                
                // And does not exist in the test code path
                if (!file_exists($this->testCodePath.$relativePath.'/'.$filename)) {
                    
                    // Create it
                    if ($output->isVerbose()) {
                        
                        echo "Creating $relativePath/$filename in the test code path...";
                    }
                    
                    file_put_contents($this->testCodePath.$relativePath.'/'.$filename, '');
                }  
                    
                // Let's try generating the test file
                $input['class'] = $this->moduleName.$namespaceRelativePath."\\".basename($child,'.php');
                $input['class-source'] = $directory.'/'.$child;
                $input['test-class'] = $this->moduleName."Test".$namespaceRelativePath."\\".basename($filename,'.php');
                $input['test-source'] = $this->testCodePath.$relativePath.'/'.$filename;
                
                $generator = $this->getGenerator($input);
                
                $generator->write();

                if ($output->isVerbose()) {
                    
                    $output->writeln(
                        sprintf(
                            'Wrote skeleton for "%s" to "%s".',
                            $generator->getOutClassName(),
                            $generator->getOutSourceFile()
                        )
                    );  
                } 
            }  
        }   
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @return AbstractGenerator
     */
    protected function getGenerator($input)
    {
        return new TestGenerator(
            $input['class'],
            $input['class-source'],
            $input['test-class'],
            $input['test-source']
        );
    }
}





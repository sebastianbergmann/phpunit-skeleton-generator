<?php
/**
 * PHPUnit_SkeletonGenerator
 *
 * Copyright (c) 2012, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit
 * @subpackage SkeletonGenerator
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2012 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */

namespace SebastianBergmann\PHPUnit\SkeletonGenerator
{
    /**
     * @package    PHPUnit
     * @subpackage SkeletonGenerator
     * @author     Sebastian Bergmann <sebastian@phpunit.de>
     * @copyright  2012 Sebastian Bergmann <sebastian@phpunit.de>
     * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
     * @link       http://www.phpunit.de/
     * @since      Class available since Release 1.0.0
     */
    class Command
    {
        /**
         * Main method.
         */
        public static function main()
        {
            $input = new \ezcConsoleInput;

            $input->registerOption(
              new \ezcConsoleOption(
                '',
                'bootstrap',
                \ezcConsoleInput::TYPE_STRING
               )
            );

            $input->registerOption(
              new \ezcConsoleOption(
                '',
                'class',
                \ezcConsoleInput::TYPE_NONE
               )
            );

            $input->registerOption(
              new \ezcConsoleOption(
                '',
                'test',
                \ezcConsoleInput::TYPE_NONE
               )
            );

            $input->registerOption(
              new \ezcConsoleOption(
                'h',
                'help',
                \ezcConsoleInput::TYPE_NONE,
                NULL,
                FALSE,
                '',
                '',
                array(),
                array(),
                FALSE,
                FALSE,
                TRUE
               )
            );

            $input->registerOption(
              new \ezcConsoleOption(
                'v',
                'version',
                \ezcConsoleInput::TYPE_NONE,
                NULL,
                FALSE,
                '',
                '',
                array(),
                array(),
                FALSE,
                FALSE,
                TRUE
               )
            );

            try {
                $input->process();
            }

            catch (\ezcConsoleOptionException $e) {
                print $e->getMessage() . "\n";
                exit(1);
            }

            if ($input->getOption('help')->value) {
                self::showHelp();
                exit(0);
            }

            else if ($input->getOption('version')->value) {
                self::printVersionString();
                exit(0);
            }

            $arguments = $input->getArguments();
            $bootstrap = $input->getOption('bootstrap')->value;
            $class     = $input->getOption('class')->value;
            $test      = $input->getOption('test')->value;

            if (empty($arguments) || (!$class && !$test) || ($class && $test)) {
                self::showHelp();
                exit(1);
            }

            if ($class) {
                $reflector = new \ReflectionClass(
                  'SebastianBergmann\PHPUnit\SkeletonGenerator\ClassGenerator'
                );
            }

            else if ($test) {
                $reflector = new \ReflectionClass(
                  'SebastianBergmann\PHPUnit\SkeletonGenerator\TestGenerator'
                );
            }

            self::printVersionString();

            if ($bootstrap && file_exists($bootstrap)) {
                include $bootstrap;
            }

            $generator = $reflector->newInstanceArgs($arguments);
            $generator->write();

            printf(
              'Wrote skeleton for "%s" to "%s".' . "\n",
              $generator->getOutClassName(),
              $generator->getOutSourceFile()
            );

            exit(0);
        }

        /**
         * Shows an error.
         *
         * @param string $message
         */
        protected static function showError($message)
        {
            self::printVersionString();

            print $message;

            exit(1);
        }

        /**
         * Shows the help.
         */
        protected static function showHelp()
        {
            self::printVersionString();

            print <<<EOT
Usage: phpunit-skelgen --class ClassTest
       phpunit-skelgen --class -- ClassTest [ClassTest.php] [Class] [Class.php]
       phpunit-skelgen --test Class [Class.php] [ClassTest] [ClassTest.php]
       phpunit-skelgen --test -- Class [Class.php] [ClassTest] [ClassTest.php]

  --class             Generate Class [in Class.php] based on ClassTest [in ClassTest.php]
  --test              Generate ClassTest [in ClassTest.php] based on Class [in Class.php]

  --bootstrap <file>  A "bootstrap" PHP file that is run at startup

  --help              Print this usage information
  --version           Print the version

EOT;
        }

        /**
         * Prints the version string.
         */
        protected static function printVersionString()
        {
            printf(
              "PHPUnit Skeleton Generator %s by Sebastian Bergmann.\n\n",
              Version::id()
            );
        }
    }
}

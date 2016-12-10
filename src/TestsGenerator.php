<?php
/**
 * phpunit-skeleton-generator
 *
 * Copyright (c) 2012-2014, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2012-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 1.0.0
 */

namespace SebastianBergmann\PHPUnit\SkeletonGenerator;

use SebastianBergmann\PHPUnit\SkeletonGenerator\Utils\ClassMapGenerator;

/**
 * Generator for test class skeletons from classes.
 *
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2012-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class TestsGenerator
{
    /**
     * @param      $inFolder     source
     * @param      $outFolder    tests
     * @param      $outClassName template variable for class name
     *
     * @return array TestGenerator
     */
    function getGenerators($inFolder, $outFolder, $outClassName)
    {
        $ret = [];

        $inFolder = realpath($inFolder);
        $map      = ClassMapGenerator::createMap($inFolder);

        foreach($map as $className => $filename)
        {
            // skip non classes (traits, interfaces) but still include the file to prevent errors 
            include_once $filename;

            if(!class_exists($className))
            {
                continue;
            }

            // extract class name without namespace
            $tmp            = explode('\\', $className);
            $shortClassName = $tmp[count($tmp) - 1];

            // build generator
            $outTestClassName = str_ireplace('{classname}', $shortClassName, $outClassName);
            $ret[]            = new TestGenerator($className, $filename, $outTestClassName, $outFolder . '/' . $outTestClassName . '.php');
        }

        return $ret;
    }
}


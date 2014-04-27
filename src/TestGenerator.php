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

/**
 * Generator for test class skeletons from classes.
 *
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2012-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class TestGenerator extends AbstractGenerator
{
    /**
     * @var array
     */
    protected $methodNameCounter = array();

    /**
     * Constructor.
     *
     * @param string $inClassName
     * @param string $inSourceFile
     * @param string $outClassName
     * @param string $outSourceFile
     * @throws \RuntimeException
     */
    public function __construct($inClassName, $inSourceFile = '', $outClassName = '', $outSourceFile = '')
    {
        if (class_exists($inClassName)) {
            $reflector    = new \ReflectionClass($inClassName);
            $inSourceFile = $reflector->getFileName();

            if ($inSourceFile === false) {
                $inSourceFile = '<internal>';
            }

            unset($reflector);
        } else {
            if (empty($inSourceFile)) {
                $possibleFilenames = array(
                    $inClassName . '.php',
                    str_replace(
                        array('_', '\\'),
                        DIRECTORY_SEPARATOR,
                        $inClassName
                    ) . '.php'
                );

                foreach ($possibleFilenames as $possibleFilename) {
                    if (is_file($possibleFilename)) {
                        $inSourceFile = $possibleFilename;
                    }
                }
            }

            if (empty($inSourceFile)) {
                throw new \RuntimeException(
                    sprintf(
                        'Neither "%s" nor "%s" could be opened.',
                        $possibleFilenames[0],
                        $possibleFilenames[1]
                    )
                );
            }

            if (!is_file($inSourceFile)) {
                throw new \RuntimeException(
                    sprintf(
                        '"%s" could not be opened.',
                        $inSourceFile
                    )
                );
            }

            $inSourceFile = realpath($inSourceFile);
            include_once $inSourceFile;

            if (!class_exists($inClassName)) {
                throw new \RuntimeException(
                    sprintf(
                        'Could not find class "%s" in "%s".',
                        $inClassName,
                        $inSourceFile
                    )
                );
            }
        }

        if (empty($outClassName)) {
            $outClassName = $inClassName . 'Test';
        }

        if (empty($outSourceFile)) {
            $outSourceFile = dirname($inSourceFile) . DIRECTORY_SEPARATOR . $outClassName . '.php';
        }

        parent::__construct(
            $inClassName,
            $inSourceFile,
            $outClassName,
            $outSourceFile
        );
    }

    /**
     * @return string
     */
    public function generate()
    {
        $class = new \ReflectionClass(
            $this->inClassName['fullyQualifiedClassName']
        );

        $methods           = '';
        $incompleteMethods = '';

        foreach ($class->getMethods() as $method) {
            if (!$method->isConstructor() &&
                !$method->isAbstract() &&
                $method->isPublic() &&
                $method->getDeclaringClass()->getName() == $this->inClassName['fullyQualifiedClassName']) {
                $assertAnnotationFound = false;

                if (preg_match_all('/@assert(.*)$/Um', $method->getDocComment(), $annotations)) {
                    foreach ($annotations[1] as $annotation) {
                        if (preg_match('/\((.*)\)\s+([^\s]*)\s+(.*)/', $annotation, $matches)) {
                            switch ($matches[2]) {
                                case '==':
                                    $assertion = 'Equals';
                                    break;

                                case '!=':
                                    $assertion = 'NotEquals';
                                    break;

                                case '===':
                                    $assertion = 'Same';
                                    break;

                                case '!==':
                                    $assertion = 'NotSame';
                                    break;

                                case '>':
                                    $assertion = 'GreaterThan';
                                    break;

                                case '>=':
                                    $assertion = 'GreaterThanOrEqual';
                                    break;

                                case '<':
                                    $assertion = 'LessThan';
                                    break;

                                case '<=':
                                    $assertion = 'LessThanOrEqual';
                                    break;

                                case 'throws':
                                    $assertion = 'exception';
                                    break;

                                default:
                                    throw new \RuntimeException(
                                        sprintf(
                                            'Token "%s" could not be parsed in @assert annotation.',
                                            $matches[2]
                                        )
                                    );
                            }

                            if ($assertion == 'exception') {
                                $template = 'TestMethodException';
                            } elseif ($assertion == 'Equals' && strtolower($matches[3]) == 'true') {
                                $assertion = 'True';
                                $template  = 'TestMethodBool';
                            } elseif ($assertion == 'NotEquals' && strtolower($matches[3]) == 'true') {
                                $assertion = 'False';
                                $template  = 'TestMethodBool';
                            } elseif ($assertion == 'Equals' && strtolower($matches[3]) == 'false') {
                                $assertion = 'False';
                                $template  = 'TestMethodBool';
                            } elseif ($assertion == 'NotEquals' && strtolower($matches[3]) == 'false') {
                                $assertion = 'True';
                                $template  = 'TestMethodBool';
                            } else {
                                $template = 'TestMethod';
                            }

                            if ($method->isStatic()) {
                                $template .= 'Static';
                            }

                            $methodTemplate = new \Text_Template(
                                sprintf(
                                    '%s%stemplate%s%s.tpl',
                                    __DIR__,
                                    DIRECTORY_SEPARATOR,
                                    DIRECTORY_SEPARATOR,
                                    $template
                                )
                            );

                            $origMethodName = $method->getName();
                            $methodName     = ucfirst($origMethodName);

                            if (isset($this->methodNameCounter[$methodName])) {
                                $this->methodNameCounter[$methodName]++;
                            } else {
                                $this->methodNameCounter[$methodName] = 1;
                            }

                            if ($this->methodNameCounter[$methodName] > 1) {
                                $methodName .= $this->methodNameCounter[$methodName];
                            }

                            $methodTemplate->setVar(
                                array(
                                    'annotation'     => trim($annotation),
                                    'arguments'      => $matches[1],
                                    'assertion'      => isset($assertion) ? $assertion : '',
                                    'expected'       => $matches[3],
                                    'origMethodName' => $origMethodName,
                                    'className'      => $this->inClassName['fullyQualifiedClassName'],
                                    'methodName'     => $methodName
                                )
                            );

                            $methods .= $methodTemplate->render();

                            $assertAnnotationFound = true;
                        }
                    }
                }

                if (!$assertAnnotationFound) {
                    $methodTemplate = new \Text_Template(
                        sprintf(
                            '%s%stemplate%sIncompleteTestMethod.tpl',
                            __DIR__,
                            DIRECTORY_SEPARATOR,
                            DIRECTORY_SEPARATOR
                        )
                    );

                    $methodTemplate->setVar(
                        array(
                            'className'      => $this->inClassName['fullyQualifiedClassName'],
                            'methodName'     => ucfirst($method->getName()),
                            'origMethodName' => $method->getName()
                        )
                    );

                    $incompleteMethods .= $methodTemplate->render();
                }
            }
        }

        $classTemplate = new \Text_Template(
            sprintf(
                '%s%stemplate%sTestClass.tpl',
                __DIR__,
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR
            )
        );

        if ($this->outClassName['namespace'] != '') {
            $namespace = "\nnamespace " .
                $this->outClassName['namespace'] . ";\n";
        } else {
            $namespace = '';
        }

        $classTemplate->setVar(
            array(
                'namespace'          => $namespace,
                'namespaceSeparator' => !empty($namespace) ? '\\' : '',
                'className'          => $this->inClassName['className'],
                'testClassName'      => $this->outClassName['className'],
                'methods'            => $methods . $incompleteMethods,
                'date'               => date('Y-m-d'),
                'time'               => date('H:i:s')
            )
        );

        return $classTemplate->render();
    }
}

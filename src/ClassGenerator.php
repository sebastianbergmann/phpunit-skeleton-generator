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
 * Generator for class skeletons from test classes.
 *
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2012-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 1.0.0
 */
class ClassGenerator extends AbstractGenerator
{
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
        if (empty($inSourceFile)) {
            $inSourceFile = $inClassName . '.php';
        }

        if (!is_file($inSourceFile)) {
            throw new \RuntimeException(
                sprintf(
                    '"%s" could not be opened.',
                    $inSourceFile
                )
            );
        }

        if (empty($outClassName)) {
            $outClassName = substr($inClassName, 0, strlen($inClassName) - 4);
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
        $methods = '';

        foreach ($this->findTestedMethods() as $method) {
            $methodTemplate = new \Text_Template(
                sprintf(
                    '%s%stemplate%sMethod.tpl',
                    __DIR__,
                    DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR
                )
            );

            $methodTemplate->setVar(
                array('methodName' => $method)
            );

            $methods .= $methodTemplate->render();
        }

        $classTemplate = new \Text_Template(
            sprintf(
                '%s%stemplate%sClass.tpl',
                __DIR__,
                DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR
            )
        );

        $classTemplate->setVar(
            array(
                'className' => $this->outClassName['fullyQualifiedClassName'],
                'methods'   => $methods,
                'date'      => date('Y-m-d'),
                'time'      => date('H:i:s')
            )
        );

        return $classTemplate->render();
    }

    /**
     * Returns the methods of the class under test
     * that are called from the test methods.
     *
     * @return array
     */
    protected function findTestedMethods()
    {
        $setUpVariables = array();
        $testedMethods  = array();
        $classes        = $this->getClassesInFile($this->inSourceFile);
        $testMethods    = $classes[$this->inClassName['fullyQualifiedClassName']]['methods'];
        unset($classes);

        foreach ($testMethods as $name => $testMethod) {
            if (strtolower($name) == 'setup') {
                $setUpVariables = $this->findVariablesThatReferenceClass(
                    $testMethod['tokens']
                );

                break;
            }
        }

        foreach ($testMethods as $name => $testMethod) {
            $argVariables = array();

            if (strtolower($name) == 'setup') {
                continue;
            }

            $start = strpos($testMethod['signature'], '(') + 1;
            $end   = strlen($testMethod['signature']) - $start - 1;
            $args  = substr($testMethod['signature'], $start, $end);

            foreach (explode(',', $args) as $arg) {
                $arg = explode(' ', trim($arg));

                if (count($arg) == 2) {
                    $type = $arg[0];
                    $var  = $arg[1];
                } else {
                    $type = null;
                    $var  = $arg[0];
                }

                if ($type == $this->outClassName['fullyQualifiedClassName']) {
                    $argVariables[] = $var;
                }
            }

            $variables = array_unique(
                array_merge(
                    $setUpVariables,
                    $argVariables,
                    $this->findVariablesThatReferenceClass($testMethod['tokens'])
                )
            );

            foreach ($testMethod['tokens'] as $i => $token) {
                if (is_array($token) && $token[0] == T_DOUBLE_COLON &&
                    is_array($testMethod['tokens'][$i-1]) &&
                    $testMethod['tokens'][$i-1][0] == T_STRING &&
                    $testMethod['tokens'][$i-1][1] == $this->outClassName['fullyQualifiedClassName'] &&
                    is_array($testMethod['tokens'][$i+1]) &&
                    $testMethod['tokens'][$i+1][0] == T_STRING &&
                    $testMethod['tokens'][$i+2] == '(') {
                    // Class::method()
                    $testedMethods[] = $testMethod['tokens'][$i+1][1];
                } elseif (is_array($token) && $token[0] == T_OBJECT_OPERATOR &&
                    in_array($this->findVariableName($testMethod['tokens'], $i), $variables) &&
                    is_array($testMethod['tokens'][$i+2]) &&
                    $testMethod['tokens'][$i+2][0] == T_OBJECT_OPERATOR &&
                    is_array($testMethod['tokens'][$i+3]) &&
                    $testMethod['tokens'][$i+3][0] == T_STRING &&
                    $testMethod['tokens'][$i+4] == '(') {
                    // $this->object->method()
                    $testedMethods[] = $testMethod['tokens'][$i+3][1];
                } elseif (is_array($token) && $token[0] == T_OBJECT_OPERATOR &&
                    in_array($this->findVariableName($testMethod['tokens'], $i), $variables) &&
                    is_array($testMethod['tokens'][$i+1]) &&
                    $testMethod['tokens'][$i+1][0] == T_STRING &&
                    $testMethod['tokens'][$i+2] == '(') {
                    // $object->method()
                    $testedMethods[] = $testMethod['tokens'][$i+1][1];
                }
            }
        }

        $testedMethods = array_unique($testedMethods);
        sort($testedMethods);

        return $testedMethods;
    }

    /**
     * Returns the variables used in test methods
     * that reference the class under test.
     *
     * @param  array $tokens
     * @return array
     */
    protected function findVariablesThatReferenceClass(array $tokens)
    {
        $inNew     = false;
        $variables = array();

        foreach ($tokens as $i => $token) {
            if (is_string($token)) {
                if (trim($token) == ';') {
                    $inNew = false;
                }

                continue;
            }

            list ($token, $value) = $token;

            switch ($token) {
                case T_NEW:
                    $inNew = true;
                    break;

                case T_STRING:
                    if ($inNew) {
                        if ($value == $this->outClassName['fullyQualifiedClassName']) {
                            $variables[] = $this->findVariableName($tokens, $i);
                        }
                    }

                    $inNew = false;
                    break;
            }
        }

        return $variables;
    }

    /**
     * Finds the variable name of the object for the method call
     * that is currently being processed.
     *
     * @param  array   $tokens
     * @param  integer $start
     * @return mixed
     */
    protected function findVariableName(array $tokens, $start)
    {
        for ($i = $start - 1; $i >= 0; $i--) {
            if (is_array($tokens[$i]) && $tokens[$i][0] == T_VARIABLE) {
                $variable = $tokens[$i][1];

                if (is_array($tokens[$i+1]) &&
                    $tokens[$i+1][0] == T_OBJECT_OPERATOR &&
                    $tokens[$i+2] != '(' &&
                    $tokens[$i+3] != '(') {
                    $variable .= '->' . $tokens[$i+2][1];
                }

                return $variable;
            }
        }

        return false;
    }

    /**
     * @param  string $filename
     * @return array
     */
    protected function getClassesInFile($filename)
    {
        $result = array();

        $tokens                     = token_get_all(
            file_get_contents($filename)
        );
        $numTokens                  = count($tokens);
        $blocks                     = array();
        $line                       = 1;
        $currentBlock               = false;
        $currentNamespace           = false;
        $currentClass               = false;
        $currentFunction            = false;
        $currentFunctionStartLine   = false;
        $currentFunctionTokens      = array();
        $currentDocComment          = false;
        $currentSignature           = false;
        $currentSignatureStartToken = false;

        for ($i = 0; $i < $numTokens; $i++) {
            if ($currentFunction !== false) {
                $currentFunctionTokens[] = $tokens[$i];
            }

            if (is_string($tokens[$i])) {
                if ($tokens[$i] == '{') {
                    if ($currentBlock == T_CLASS) {
                        $block = $currentClass;
                    } elseif ($currentBlock == T_FUNCTION) {
                        $currentSignature = '';

                        for ($j = $currentSignatureStartToken; $j < $i; $j++) {
                            if (is_string($tokens[$j])) {
                                $currentSignature .= $tokens[$j];
                            } else {
                                $currentSignature .= $tokens[$j][1];
                            }
                        }

                        $currentSignature = trim($currentSignature);

                        $block                      = $currentFunction;
                        $currentSignatureStartToken = false;
                    } else {
                        $block = false;
                    }

                    array_push($blocks, $block);

                    $currentBlock = false;
                } elseif ($tokens[$i] == '}') {
                    $block = array_pop($blocks);

                    if ($block !== false && $block !== null) {
                        if ($block == $currentFunction) {
                            if ($currentDocComment !== false) {
                                $docComment        = $currentDocComment;
                                $currentDocComment = false;
                            } else {
                                $docComment = '';
                            }

                            $tmp = array(
                                'docComment' => $docComment,
                                'signature'  => $currentSignature,
                                'startLine'  => $currentFunctionStartLine,
                                'endLine'    => $line,
                                'tokens'     => $currentFunctionTokens
                            );

                            if ($currentClass !== false) {
                                $result[$currentClass]['methods'][$currentFunction] = $tmp;
                            }

                            $currentFunction          = false;
                            $currentFunctionStartLine = false;
                            $currentFunctionTokens    = array();
                            $currentSignature         = false;
                        } elseif ($block == $currentClass) {
                            $result[$currentClass]['endLine'] = $line;

                            $currentClass          = false;
                            $currentClassStartLine = false;
                        }
                    }
                }

                continue;
            }

            switch ($tokens[$i][0]) {
                case T_HALT_COMPILER:
                    return;
                    break;

                case T_NAMESPACE:
                    $currentNamespace = $tokens[$i+2][1];

                    for ($j = $i+3; $j < $numTokens; $j += 2) {
                        if ($tokens[$j][0] == T_NS_SEPARATOR) {
                            $currentNamespace .= '\\' . $tokens[$j+1][1];
                        } else {
                            break;
                        }
                    }
                    break;

                case T_CURLY_OPEN:
                    $currentBlock = T_CURLY_OPEN;
                    array_push($blocks, $currentBlock);
                    break;

                case T_DOLLAR_OPEN_CURLY_BRACES:
                    $currentBlock = T_DOLLAR_OPEN_CURLY_BRACES;
                    array_push($blocks, $currentBlock);
                    break;

                case T_CLASS:
                    $currentBlock = T_CLASS;

                    if ($currentNamespace === false) {
                        $currentClass = $tokens[$i+2][1];
                    } else {
                        $currentClass = $currentNamespace . '\\' .
                            $tokens[$i+2][1];
                    }

                    if ($currentDocComment !== false) {
                        $docComment        = $currentDocComment;
                        $currentDocComment = false;
                    } else {
                        $docComment = '';
                    }

                    $result[$currentClass] = array(
                        'methods'    => array(),
                        'docComment' => $docComment,
                        'startLine'  => $line
                    );
                    break;

                case T_FUNCTION:
                    if (!((is_array($tokens[$i+2]) &&
                            $tokens[$i+2][0] == T_STRING) ||
                        (is_string($tokens[$i+2]) &&
                            $tokens[$i+2] == '&' &&
                            is_array($tokens[$i+3]) &&
                            $tokens[$i+3][0] == T_STRING))) {
                        continue;
                    }

                    $currentBlock             = T_FUNCTION;
                    $currentFunctionStartLine = $line;

                    $done                       = false;
                    $currentSignatureStartToken = $i - 1;

                    do {
                        switch ($tokens[$currentSignatureStartToken][0]) {
                            case T_ABSTRACT:
                            case T_FINAL:
                            case T_PRIVATE:
                            case T_PUBLIC:
                            case T_PROTECTED:
                            case T_STATIC:
                            case T_WHITESPACE:
                                $currentSignatureStartToken--;
                                break;

                            default:
                                $currentSignatureStartToken++;
                                $done = true;
                        }
                    } while (!$done);

                    if (isset($tokens[$i+2][1])) {
                        $functionName = $tokens[$i+2][1];
                    } elseif (isset($tokens[$i+3][1])) {
                        $functionName = $tokens[$i+3][1];
                    }

                    if ($currentNamespace === false) {
                        $currentFunction = $functionName;
                    } else {
                        $currentFunction = $currentNamespace . '\\' .
                            $functionName;
                    }
                    break;

                case T_DOC_COMMENT:
                    $currentDocComment = $tokens[$i][1];
                    break;
            }

            $line += substr_count($tokens[$i][1], "\n");
        }

        return $result;
    }
}

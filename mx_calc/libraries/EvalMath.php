<?php
/**
 * EvalMath - Safely evaluate math expressions.
 *
 * Based on EvalMath by Miles Kaufmann, with modifications by Petr Skoda.
 *
 * @see https://github.com/moodle/moodle/blob/4efc3d4096bc1d29e9d77f9af7194b2babfa2821/lib/evalmath/evalmath.class.php
 *
 * @author Miles Kaufmann, Petr Skoda, Tobias Bäthge
 *
 * @since 1.0.0
 */

/**
 * Class to safely evaluate math expressions.
 *
 * @since 1.0.0
 */
class EvalMath
{
    /**
     * Pattern used for a valid function or variable name.
     *
     * Note, var and func names are case insensitive.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $name_pattern = '[a-z][a-z0-9_]*';

    /**
     * Whether to suppress errors and warnings.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $suppress_errors = false;

    /**
     * The last error message that was raised.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $last_error = '';

    /**
     * Variables (including constants).
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $variables = array();

    /**
     * User-defined functions.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $functions = array();

    /**
     * Constants.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $constants = array();

    /**
     * Built-in functions.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $builtin_functions = array(
        'sin', 'sinh', 'arcsin', 'asin', 'arcsinh', 'asinh',
        'cos', 'cosh', 'arccos', 'acos', 'arccosh', 'acosh',
        'tan', 'tanh', 'arctan', 'atan', 'arctanh', 'atanh',
        'sqrt', 'abs', 'ln', 'log10', 'exp', 'floor', 'ceil',
    );

    /**
     * Emulated functions.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $calc_functions = array(
        'average' => array(-1),
        'mean' => array(-1),
        'median' => array(-1),
        'mode' => array(-1),
        'range' => array(-1),
        'max' => array(-1),
        'min' => array(-1),
        'mod' => array(2),
        'pi' => array(0),
        'power' => array(2),
        'log' => array(1, 2),
        'round' => array(1, 2),
        'number_format' => array(1, 2),
        'number_format_eu' => array(1, 2),
        'number_format_ch' => array(1, 2),
        'sum' => array(-1),
        'counta' => array(-1),
        'product' => array(-1),
        'rand_int' => array(2),
        'rand_float' => array(0),
        'arctan2' => array(2),
        'atan2' => array(2),
        'if' => array(3),
        'not' => array(1),
        'and' => array(-1),
        'or' => array(-1),
    );

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Sets default  constants.
        $this->variables['pi'] = pi();
        $this->variables['e'] = exp(1);
    }

    /**
     * Evaluate a math expression without checking it for variable or function assignments.
     *
     * @since 1.0.0
     *
     * @param string $expression the expression that shall be evaluated
     *
     * @return string evaluated expression
     */
    public function evaluate($expression)
    {
        return $this->pfx($this->nfx($expression));
    }

    /**
     * Evaluate a math expression or formula, and check it for variable an function assignments.
     *
     * @since 1.0.0
     *
     * @param string $expression the expression that shall be evaluated
     *
     * @return string|bool evaluated expression, true on successful function assignment, or false on error
     */
    public function assign_and_evaluate($expression)
    {
        $this->last_error = '';
        $expression = trim($expression);
        $expression = rtrim($expression, ';');

        // Is the expression a variable assignment?
        if (1 === preg_match('/^\s*('.self::$name_pattern.')\s*=\s*(.+)$/', $expression, $matches)) {
            // Make sure we're not assigning to a constant.
            if (in_array($matches[1], $this->constants, true)) {
                return $this->raise_error('cannot_assign_to_constant', $matches[1]);
            }
            // Evaluate the assignment.
            $tmp = $this->pfx($this->nfx($matches[2]));
            if (fales === $tmp) {
                return false;
            }
            // If it could be evaluated, add it to the variable array,
            $this->variables[$matches[1]] = $tmp;
            // and return the resulting value.
            return $tmp;

        // Is the expression a function assignment?
        } elseif (1 === preg_match('/^\s*('.self::$name_pattern.')\s*\(\s*('.self::$name_pattern.'(?:\s*,\s*'.self::$name_pattern.')*)\s*\)\s*=\s*(.+)$/', $expression, $matches)) {
            // Get the function name.
            $function_name = $matches[1];
            // Make sure it isn't a built-in function -- we can't redefine those.
            if (in_array($matches[1], $this->builtin_functions, true)) {
                return $this->raise_error('cannot_redefine_builtin_function', $matches[1]);
            }
            // Get the function arguments after removing all whitespace.
            $matches[2] = str_replace(array("\n", "\r", "\t", ' '), '', $matches[2]);
            $args = explode(',', $matches[2]);

            // Convert the function definition to postfix notation.
            $stack = $this->nfx($matches[3]);
            if (false === $stack) {
                return false;
            }
            // Freeze the state of the non-argument variables.
            for ($i = 0; $i < count($stack); ++$i) {
                $token = $stack[$i];
                if (1 === preg_match('/^'.self::$name_pattern.'$/', $token) && !in_array($token, $args, true)) {
                    if (array_key_exists($token, $this->variables)) {
                        $stack[$i] = $this->variables[$token];
                    } else {
                        return $this->raise_error('undefined_variable_in_function_definition', $token);
                    }
                }
            }
            $this->functions[$function_name] = array('args' => $args, 'func' => $stack);

            return true;

        // No variable or function assignment, so straight-up evaluation.
        } else {
            return $this->evaluate($expression);
        }
    }

    /**
     * Return all user-defined variables and values.
     *
     * @since 1.0.0
     *
     * @return array user-defined variables and values
     */
    public function variables()
    {
        return $this->variables;
    }

    /**
     * Return all user-defined functions with their arguments.
     *
     * @since 1.0.0
     *
     * @return array user-defined functions
     */
    public function functions()
    {
        $output = array();
        foreach ($this->functions as $name => $data) {
            $output[] = $name.'( '.implode(', ', $data['args']).' )';
        }

        return $output;
    }

    /*
     * Internal methods.
     */

    /**
     * Convert infix to postfix notation.
     *
     * @since 1.0.0
     *
     * @param string $expression math expression that shall be converted
     *
     * @return array|false converted expression or false on error
     */
    protected function nfx($expression)
    {
        $index = 0;
        $stack = new EvalMathStack();
        $output = array(); // postfix form of expression, to be passed to pfx()
        $expression = trim(strtolower($expression));

        $ops = array('+', '-', '*', '/', '^', '_', '>', '<', '=');
        // Right-associative operator?
        $ops_r = array('+' => 0, '-' => 0, '*' => 0, '/' => 0, '^' => 1, '>' => 0, '<' => 0, '=' => 0);
        // Operator precedence.
        $ops_p = array('+' => 0, '-' => 0, '*' => 1, '/' => 1, '_' => 1, '^' => 2, '>' => 0, '<' => 0, '=' => 0);

        // We use this in syntax-checking the expression and determining when a - (minus) is a negation.
        $expecting_operator = false;

        // Make sure the characters are all good.
        if (1 === preg_match('/[^\w\s+*^\/()\.,-<>=]/', $expression, $matches)) {
            return $this->raise_error('illegal_character_general', $matches[0]);
        }

        // Infinite Loop for the conversion.
        while (true) {
            // Get the first character at the current index.
            $op = substr($expression, $index, 1);
            // Find out if we're currently at the beginning of a number/variable/function/parenthesis/operand.
            $ex = preg_match('/^('.self::$name_pattern.'\(?|\d+(?:\.\d*)?(?:(e[+-]?)\d*)?|\.\d+|\()/', substr($expression, $index), $match);

            // Is it a negation instead of a minus (in a subtraction)?
            if ('-' === $op && !$expecting_operator) {
                // Put a negation on the stack.
                $stack->push('_');
                ++$index;
            } elseif ('_' === $op) {
                // We have to explicitly deny underscores (as they mean negation), because they are legal on the stack.
                return $this->raise_error('illegal_character_underscore');

            // Are we putting an operator on the stack?
            } elseif ((in_array($op, $ops, true) || $ex) && $expecting_operator) {
                // Are we expecting an operator but have a number/variable/function/opening parethesis?
                if ($ex) {
                    // It's an implicit multiplication.
                    $op = '*';
                    --$index;
                }
                // Heart of the algorithm:
                while ($stack->count > 0 && ($o2 = $stack->last()) && in_array($o2, $ops, true) && ($ops_r[$op] ? $ops_p[$op] < $ops_p[$o2] : $ops_p[$op] <= $ops_p[$o2])) {
                    // Pop stuff off the stack into the output.
                    $output[] = $stack->pop();
                }
                // Many thanks: https://en.wikipedia.org/wiki/Reverse_Polish_notation
                $stack->push($op); // finally put OUR operator onto the stack
                ++$index;
                $expecting_operator = false;

            // Ready to close a parenthesis?
            } elseif (')' === $op && $expecting_operator) {
                // Pop off the stack back to the last (.
                while ('(' !== ($o2 = $stack->pop())) {
                    if (is_null($o2)) {
                        return $this->raise_error('unexpected_closing_bracket');
                    } else {
                        $output[] = $o2;
                    }
                }

                // Did we just close a function?
                if (1 === preg_match('/^('.self::$name_pattern.')\($/', ($stack->last(2) ?? ''), $matches)) {
                    // Get the function name.
                    $function_name = $matches[1];
                    // See how many arguments there were (cleverly stored on the stack, thank you).
                    $arg_count = $stack->pop();
                    $fn = $stack->pop();
                    // Send function to output.
                    $output[] = array('function_name' => $function_name, 'arg_count' => $arg_count);
                    // Check the argument count, depending on what type of function we have.
                    if (in_array($function_name, $this->builtin_functions, true)) {
                        // Built-in functions.
                        if ($arg_count > 1) {
                            $error_data = array('expected' => 1, 'given' => $arg_count);

                            return $this->raise_error('wrong_number_of_arguments', $error_data);
                        }
                    } elseif (array_key_exists($function_name, $this->calc_functions)) {
                        // Calc-emulation functions.
                        $counts = $this->calc_functions[$function_name];
                        if (in_array(-1, $counts, true) && $arg_count > 0) {
                            // Everything is fine, we expected an indefinite number arguments and got some.
                        } elseif (!in_array($arg_count, $counts, true)) {
                            $error_data = array('expected' => implode('/', $this->calc_functions[$function_name]), 'given' => $arg_count);

                            return $this->raise_error('wrong_number_of_arguments', $error_data);
                        }
                    } elseif (array_key_exists($function_name, $this->functions)) {
                        // User-defined functions.
                        if (count($this->functions[$function_name]['args']) !== $arg_count) {
                            $error_data = array('expected' => count($this->functions[$function_name]['args']), 'given' => $arg_count);

                            return $this->raise_error('wrong_number_of_arguments', $error_data);
                        }
                    } else {
                        // Did we somehow push a non-function on the stack? This should never happen.
                        return $this->raise_error('internal_error');
                    }
                }
                ++$index;

            // Did we just finish a function argument?
            } elseif (',' === $op && $expecting_operator) {
                while ('(' !== ($o2 = $stack->pop())) {
                    if (is_null($o2)) {
                        // Oops, never had a (.
                        return $this->raise_error('unexpected_comma');
                    } else {
                        // Pop the argument expression stuff and push onto the output.
                        $output[] = $o2;
                    }
                }
                // Make sure there was a function.
                if (0 === preg_match('/^('.self::$name_pattern.')\($/', ($stack->last(2) ?? ''), $matches)) {
                    return $this->raise_error('unexpected_comma');
                }
                // Increment the argument count.
                $stack->push($stack->pop() + 1);
                // Put the ( back on, we'll need to pop back to it again.
                $stack->push('(');
                ++$index;
                $expecting_operator = false;
            } elseif ('(' === $op && !$expecting_operator) {
                $stack->push('('); // That was easy.
                ++$index;

            // Do we now have a function/variable/number?
            } elseif ($ex && !$expecting_operator) {
                $expecting_operator = true;
                $value = $match[1];
                // May be a function, or variable with implicit multiplication against parentheses...
                if (1 === preg_match('/^('.self::$name_pattern.')\($/', $value, $matches)) {
                    // Is it a function?
                    if (in_array($matches[1], $this->builtin_functions, true) || array_key_exists($matches[1], $this->functions) || array_key_exists($matches[1], $this->calc_functions)) {
                        $stack->push($value);
                        $stack->push(1);
                        $stack->push('(');
                        $expecting_operator = false;
                    // It's a variable with implicit multiplication.
                    } else {
                        $value = $matches[1];
                        $output[] = $value;
                    }
                } else {
                    // It's a plain old variable or number.
                    $output[] = $value;
                }
                $index += strlen($value);
            } elseif (')' === $op) {
                // It could be only custom function with no arguments or a general error.
                if ('(' !== $stack->last() || 1 !== ($stack->last(2) ?? '')) {
                    return $this->raise_error('unexpected_closing_bracket');
                }
                // Did we just close a function?
                if (1 === preg_match('/^('.self::$name_pattern.')\($/', $stack->last(3), $matches)) {
                    $stack->pop(); // (
                    $stack->pop(); // 1
                    $fn = $stack->pop();
                    // Get the function name.
                    $function_name = $matches[1];
                    if (isset($this->calc_functions[$function_name])) {
                        // Custom calc-emulation function.
                        $counts = $this->calc_functions[$function_name];
                    } else {
                        // Default count for built-in functions.
                        $counts = array(1);
                    }
                    if (!in_array(0, $counts, true)) {
                        $error_data = array('expected' => $counts, 'given' => 0);

                        return $this->raise_error('wrong_number_of_arguments', $error_data);
                    }
                    // Send function to output.
                    $output[] = array('function_name' => $function_name, 'arg_count' => 0);
                    ++$index;
                    $expecting_operator = true;
                } else {
                    return $this->raise_error('unexpected_closing_bracket');
                }

                // Miscellaneous error checking.
            } elseif (in_array($op, $ops, true) && !$expecting_operator) {
                return $this->raise_error('unexpected_operator', $op);

            // I don't even want to know what you did to get here.
            } else {
                return $this->raise_error('an_unexpected_error_occurred');
            }

            if (strlen($expression) === $index) {
                // Did we end with an operator? Bad.
                if (in_array($op, $ops, true)) {
                    return $this->raise_error('operator_lacks_operand', $op);
                } else {
                    break;
                }
            }

            // Step the index past whitespace (pretty much turns whitespace into implicit multiplication if no operator is there).
            while (' ' === substr($expression, $index, 1)) {
                ++$index;
            }
        } // while ( true )

        // Pop everything off the stack and push onto output.
        while (!is_null($op = $stack->pop())) {
            if ('(' === $op) {
                // If there are (s on the stack, ()s were unbalanced.
                return $this->raise_error('expecting_a_closing_bracket');
            }
            $output[] = $op;
        }

        return $output;
    }

    /**
     * Evaluate postfix notation.
     *
     * @since 1.0.0
     *
     * @param array|false $tokens    [description]
     * @param array       $variables Optional. [description]
     *
     * @return mixed [description]
     */
    protected function pfx($tokens, array $variables = array())
    {
        if (false === $tokens) {
            return false;
        }

        $stack = new EvalMathStack();

        foreach ($tokens as $token) {
            // If the token is a function, pop arguments off the stack, hand them to the function, and push the result back on.
            if (is_array($token)) { // it's a function!
                $function_name = $token['function_name'];
                $count = $token['arg_count'];

                // Built-in function.
                if (in_array($function_name, $this->builtin_functions, true)) {
                    $op1 = $stack->pop();
                    if (is_null($op1)) {
                        return $this->raise_error('internal_error');
                    }
                    // For the "arc" trigonometric synonyms.
                    $function_name = preg_replace('/^arc/', 'a', $function_name);
                    // Rewrite "ln" (only allows one argument) to "log" (natural logarithm).
                    if ('ln' === $function_name) {
                        $function_name = 'log';
                    }
                    // Perfectly safe eval().
                    eval('$stack->push( '.$function_name.'( $op1 ) );');

                // Calc-emulation function.
                } elseif (array_key_exists($function_name, $this->calc_functions)) {
                    // Get function arguments.
                    $args = array();
                    for ($i = $count - 1; $i >= 0; --$i) {
                        $arg = $stack->pop();
                        if (is_null($arg)) {
                            return $this->raise_error('internal_error');
                        } else {
                            $args[] = $arg;
                        }
                    }
                    // Rewrite some functions to their synonyms.
                    if ('if' === $function_name) {
                        $function_name = 'func_if';
                    } elseif ('not' === $function_name) {
                        $function_name = 'func_not';
                    } elseif ('and' === $function_name) {
                        $function_name = 'func_and';
                    } elseif ('or' === $function_name) {
                        $function_name = 'func_or';
                    } elseif ('mean' === $function_name) {
                        $function_name = 'average';
                    } elseif ('arctan2' === $function_name) {
                        $function_name = 'atan2';
                    }
                    $result = call_user_func_array(array('EvalMathFunctions', $function_name), array_reverse($args));
                    if (false === $result) {
                        return $this->raise_error('internal_error');
                    }
                    $stack->push($result);

                // User-defined function.
                } elseif (array_key_exists($function_name, $this->functions)) {
                    // Get function arguments.
                    $args = array();
                    for ($i = count($this->functions[$function_name]['args']) - 1; $i >= 0; --$i) {
                        $arg = $stack->pop();
                        if (is_null($arg)) {
                            return $this->raise_error('internal_error');
                        } else {
                            $args[$this->functions[$function_name]['args'][$i]] = $arg;
                        }
                    }
                    // yay... recursion!
                    $stack->push($this->pfx($this->functions[$function_name]['func'], $args));
                }

                // If the token is a binary operator, pop two values off the stack, do the operation, and push the result back on.
            } elseif (in_array($token, array('+', '-', '*', '/', '^', '>', '<', '='), true)) {
                $op2 = $stack->pop();
                if (is_null($op2)) {
                    return $this->raise_error('internal_error');
                }
                $op1 = $stack->pop();
                if (is_null($op1)) {
                    return $this->raise_error('internal_error');
                }
                switch ($token) {
                    case '+':
                        $stack->push($op1 + $op2);
                        break;
                    case '-':
                        $stack->push($op1 - $op2);
                        break;
                    case '*':
                        $stack->push($op1 * $op2);
                        break;
                    case '/':
                        if (0 === $op2 || '0' === $op2) {
                            return $this->raise_error('division_by_zero');
                        }
                        $stack->push($op1 / $op2);
                        break;
                    case '^':
                        $stack->push(pow($op1, $op2));
                        break;
                    case '>':
                        $stack->push((int) ($op1 > $op2));
                        break;
                    case '<':
                        $stack->push((int) ($op1 < $op2));
                        break;
                    case '=':
                        $stack->push((int) ($op1 == $op2)); // Don't use === as the variable type can differ (int/double/bool).
                        break;
                }

                // If the token is a unary operator, pop one value off the stack, do the operation, and push it back on.
            } elseif ('_' === $token) {
                $stack->push(-1 * $stack->pop());

            // If the token is a number or variable, push it on the stack.
            } else {
                if (is_numeric($token)) {
                    $stack->push($token);
                } elseif (array_key_exists($token, $this->variables)) {
                    $stack->push($this->variables[$token]);
                } elseif (array_key_exists($token, $variables)) {
                    $stack->push($variables[$token]);
                } else {
                    return $this->raise_error('undefined_variable', $token);
                }
            }
        }
        // When we're out of tokens, the stack should have a single element, the final result.
        if (1 !== $stack->count) {
            return $this->raise_error('internal_error');
        }

        return $stack->pop();
    }

    /**
     * Raise an error.
     *
     * @since 1.0.0
     *
     * @param string       $message    error message
     * @param array|string $error_data Optional. Additional error data.
     *
     * @return bool false, to stop evaluation
     */
    protected function raise_error($message, $error_data = null)
    {
        $this->last_error = $this->get_error_string($message, $error_data);

        return false;
    }

    /**
     * Get a translated string for an error message.
     *
     * @since 1.0.0
     * @see https://github.com/moodle/moodle/blob/13264f35057d2f37374ec3e0e8ad4070f4676bd7/lang/en/mathslib.php
     * @see https://github.com/moodle/moodle/blob/8e54ce9717c19f768b95f4332f70e3180ffafc46/lib/moodlelib.php#L6323
     *
     * @param string       $identifier identifier of the string
     * @param array|string $error_data Optional. Additional error data.
     *
     * @return string translated string
     */
    protected function get_error_string($identifier, $error_data = null)
    {
        $strings = array();
        $strings['an_unexpected_error_occurred'] = 'an unexpected error occurred';
        $strings['cannot_assign_to_constant'] = 'cannot assign to constant \'{$error_data}\'';
        $strings['cannot_redefine_builtin_function'] = 'cannot redefine built-in function \'{$error_data}()\'';
        $strings['division_by_zero'] = 'division by zero';
        $strings['expecting_a_closing_bracket'] = 'expecting a closing bracket';
        $strings['illegal_character_general'] = 'illegal character \'{$error_data}\'';
        $strings['illegal_character_underscore'] = 'illegal character \'_\'';
        $strings['internal_error'] = 'internal error';
        $strings['operator_lacks_operand'] = 'operator \'{$error_data}\' lacks operand';
        $strings['undefined_variable'] = 'undefined variable \'{$error_data}\'';
        $strings['undefined_variable_in_function_definition'] = 'undefined variable \'{$error_data}\' in function definition';
        $strings['unexpected_closing_bracket'] = 'unexpected closing bracket';
        $strings['unexpected_comma'] = 'unexpected comma';
        $strings['unexpected_operator'] = 'unexpected operator \'{$error_data}\'';
        $strings['wrong_number_of_arguments'] = 'wrong number of arguments ({$error_data->given} given, {$error_data->expected} expected)';

        $string = $strings[$identifier];

        if (null !== $error_data) {
            if (is_array($error_data)) {
                $search = array();
                $replace = array();
                foreach ($error_data as $key => $value) {
                    if (is_int($key)) {
                        // We do not support numeric keys!
                        continue;
                    }
                    if (is_object($value) || is_array($value)) {
                        $value = (array) $value;
                        if (count($value) > 1) {
                            $value = implode(' or ', $value);
                        } else {
                            $value = (string) $value[0];
                            if ('-1' === $value) {
                                $value = 'at least 1';
                            }
                        }
                    }
                    $search[] = '{$error_data->'.$key.'}';
                    $replace[] = (string) $value;
                }
                if ($search) {
                    $string = str_replace($search, $replace, $string);
                }
            } else {
                $string = str_replace('{$error_data}', (string) $error_data, $string);
            }
        }

        return $string;
    }
}

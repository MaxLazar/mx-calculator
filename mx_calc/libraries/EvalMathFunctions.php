<?php

/**
 * Common math functions, prepared for usage in EvalMath.
 *
 * @since 1.0.0
 */
class EvalMathFunctions
{
    /**
     * Seed for the generation of random numbers.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $random_seed = null;

    /**
     * Choose from two values based on an if-condition.
     *
     * "if" is not a valid function name, which is why it's prefixed with "func_".
     *
     * @since  1.0.0
     *
     * @param float|int $condition condition
     * @param float|int $then      return value if the condition is true
     * @param float|int $else      return value if the condition is false
     *
     * @return float|int result of the if check
     */
    public static function func_if($condition, $then, $else)
    {
        return  (bool) $condition ? $then : $else;
    }

    /**
     * Return the negation (boolean "not") of a value.
     *
     * Similar to "func_if", the function name is prefixed with "func_", although it wouldn't be necessary.
     *
     * @since  1.0.0
     *
     * @param float|int $value value to be negated
     *
     * @return int negated value (0 for false, 1 for true)
     */
    public static function func_not($value)
    {
        return (int) !(bool) $value;
    }

    /**
     * Calculate the conjunction (boolean "and") of some values.
     *
     * "and" is not a valid function name, which is why it's prefixed with "func_".
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the conjunction shall be calculated
     *
     * @return int conjunction of the passed arguments
     */
    public static function func_and($args)
    {
        $args = func_get_args();
        foreach ($args as $value) {
            if (!$value) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * Calculate the disjunction (boolean "or") of some values.
     *
     * "or" is not a valid function name, which is why it's prefixed with "func_".
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the disjunction shall be calculated
     *
     * @return int disjunction of the passed arguments
     */
    public static function func_or($args)
    {
        $args = func_get_args();
        foreach ($args as $value) {
            if ($value) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * Return the (rounded) value of Pi.
     *
     * @since 1.0.0
     *
     * @return float rounded value of Pi
     */
    public static function pi()
    {
        return pi();
    }

    /**
     * Calculate the sum of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the sum shall be calculated
     *
     * @return float|int sum of the passed arguments
     */
    public static function sum($args)
    {
        $args = func_get_args();

        return array_sum($args);
    }

    /**
     * Count the number of non-empty arguments.
     *
     * @since 1.9.3
     *
     * @param float|int $args values for which the number of non-empty elements shall be counted
     *
     * @return float|int counted number of non-empty elements in the passed values
     */
    public static function counta($args)
    {
        $args = func_get_args();

        return count(array_filter($args));
    }

    /**
     * Calculate the product of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the product shall be calculated
     *
     * @return float|int product of the passed arguments
     */
    public static function product($args)
    {
        $args = func_get_args();

        return array_product($args);
    }

    /**
     * Calculate the average/mean value of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the average shall be calculated
     *
     * @return float|int average value of the passed arguments
     */
    public static function average($args)
    {
        $args = func_get_args();

        return  call_user_func_array(array(__CLASS__, 'sum'), $args) / count($args);
    }

    /**
     * Calculate the median of the arguments.
     *
     * For even counts of arguments, the upper median is returned.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the median shall be calculated
     *
     * @return float|int median of the passed arguments
     */
    public static function median($args)
    {
        $args = func_get_args();
        sort($args);
        $middle = floor(count($args) / 2); // Upper median for even counts.
        return $args[$middle];
    }

    /**
     * Calculate the mode of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the mode shall be calculated
     *
     * @return float|int mode of the passed arguments
     */
    public static function mode($args)
    {
        $args = func_get_args();
        $values = array_count_values($args);
        asort($values);
        end($values);

        return key($values);
    }

    /**
     * Calculate the range of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the range shall be calculated
     *
     * @return float|int range of the passed arguments
     */
    public static function range($args)
    {
        $args = func_get_args();
        sort($args);

        return end($args) - reset($args);
    }

    /**
     * Find the maximum value of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the maximum value shall be found
     *
     * @return float|int maximum value of the passed arguments
     */
    public static function max($args)
    {
        $args = func_get_args();

        return max($args);
    }

    /**
     * Find the minimum value of the arguments.
     *
     * @since 1.0.0
     *
     * @param float|int $args values for which the minimum value shall be found
     *
     * @return float|int minimum value of the passed arguments
     */
    public static function min($args)
    {
        $args = func_get_args();

        return min($args);
    }

    /**
     * Calculate the remainder of a division of two numbers.
     *
     * @since 1.0.0
     *
     * @param float|int $op1 first number (dividend)
     * @param float|int $op2 second number (divisor)
     *
     * @return float|int remainer of the division (dividend / divisor)
     */
    public static function mod($op1, $op2)
    {
        return $op1 % $op2;
    }

    /**
     * Calculate the power of a base and an exponent.
     *
     * @since 1.0.0
     *
     * @param float|int $base     base
     * @param float|int $exponent exponent
     *
     * @return float|int power base^exponent
     */
    public static function power($base, $exponent)
    {
        return pow($base, $exponent);
    }

    /**
     * Calculate the logarithm of a number to a base.
     *
     * @since 1.0.0
     *
     * @param float|int $number number
     * @param float|int $base   Optional. Base for the logarithm. Default e (for the natural logarithm).
     *
     * @return float logarithm of the number to the base
     */
    public static function log($number, $base = M_E)
    {
        return log($number, $base);
    }

    /**
     * Calculate the arc tangent of two variables.
     *
     * The signs of the numbers determine the quadrant of the result.
     *
     * @since 1.0.0
     *
     * @param float|int $op1 first number
     * @param float|int $op2 second number
     *
     * @return float arc tangent of two numbers, similar to arc tangent of $op1/op$ except for the sign
     */
    public static function atan2($op1, $op2)
    {
        return atan2($op1, $op2);
    }

    /**
     * Round a number to a given precision.
     *
     * @since 1.0.0
     *
     * @param float|int $value    number to be rounded
     * @param float|int $decimals Optional. Number of decimals after the comma after the rounding.
     *
     * @return float rounded number
     */
    public static function round($value, $decimals = 0)
    {
        return round($value, $decimals);
    }

    /**
     * Format a number with the . as the decimal separator and the , as the thousand separator, rounded to a precision.
     *
     * The is the common number format in English-language regions.
     *
     * @since 1.0.0
     *
     * @param float|int $value    number to be rounded and formatted
     * @param float|int $decimals Optional. Number of decimals after the decimal separator after the rounding.
     *
     * @return float formatted number
     */
    public static function number_format($value, $decimals = 0)
    {
        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Format a number with the , as the decimal separator and the space as the thousand separator, rounded to a precision.
     *
     * The is the common number format in non-English-language regions, mainly in Europe.
     *
     * @since 1.0.0
     *
     * @param float|int $value    number to be rounded and formatted
     * @param float|int $decimals Optional. Number of decimals after the decimal separator after the rounding.
     *
     * @return float formatted number
     */
    public static function number_format_eu($value, $decimals = 0)
    {
        return number_format($value, $decimals, ',', ' ');
    }
    
    /**
     * Format a number with the . as the decimal separator and the ' as the thousand separator, rounded to a precision.
     *
     * The is the common number format in switzerland.
     *
     * @since NEW
     *
     * @param float|int $value    number to be rounded and formatted
     * @param float|int $decimals Optional. Number of decimals after the decimal separator after the rounding.
     *
     * @return float formatted number
     */
    public static function number_format_ch($value, $decimals = 0)
    {
        return number_format($value, $decimals, ".", "'");
    }

    /**
     * Set the seed for the generation of random numbers.
     *
     * @since 1.0.0
     *
     * @param string the seed
     */
    protected static function _set_random_seed($random_seed)
    {
        self::$random_seed = $random_seed;
    }

    /**
     * Get the seed for the generation of random numbers.
     *
     * @since 1.0.0
     *
     * @return string the seed
     */
    protected static function _get_random_seed()
    {
        if (is_null(self::$random_seed)) {
            return microtime();
        } else {
            return self::$random_seed;
        }
    }

    /**
     * Get a random integer from a range.
     *
     * @since 1.0.0
     *
     * @param int $min minimum value for the range
     * @param int $max maximum value for the range
     *
     * @return int random integer from the range [$min, $max]
     */
    public static function rand_int($min, $max)
    {
        // Swap min and max value if min is bigger than max.
        if ($min > $max) {
            $tmp = $max;
            $max = $min;
            $min = $tmp;
            unset($tmp);
        }
        $number_characters = ceil(log($max + 1 - $min, '16'));
        $md5string = md5(self::_get_random_seed());
        $offset = 0;
        do {
            while (($offset + $number_characters) > strlen($md5string)) {
                $md5string .= md5($md5string);
            }
            $randomno = hexdec(substr($md5string, $offset, $number_characters));
            $offset += $number_characters;
        } while (($min + $randomno) > $max);

        return $min + $randomno;
    }

    /**
     * Get a random double value from a range [0, 1].
     *
     * @since 1.0.0
     *
     * @return float random number from the range [0, 1]
     */
    public static function rand_float()
    {
        $random_values = unpack('v', md5(self::_get_random_seed(), true));

        return array_shift($random_values) / 65536;
    }
}

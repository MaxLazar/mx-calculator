<?php
if (! defined('MX_CALC_KEY'))
{
	define('MX_CALC_NAME', 'MX Calculator');
	define('MX_CALC_VER',  '1.0.2');
	define('MX_CALC_KEY', 'mx_calc');
	define('MX_CALC_AUTHOR',  'Max Lazar');
	define('MX_CALC_DOCS',  'http://www.eec.ms/add-ons/mx-calculator');
	define('MX_CALC_DESC',  'MX Calculator provides mathematical operations in ExpressionEngine 2 templates. ');

}

/**
 * < EE 2.6.0 backward compat
 */

if ( ! function_exists('ee'))
{
    function ee()
    {
        static $EE;
        if ( ! $EE) $EE = get_instance();
        return $EE;
    }
}

/* End of file config.php */
/* Location: ./system/expressionengine/third_party/mx_calc/config.php */
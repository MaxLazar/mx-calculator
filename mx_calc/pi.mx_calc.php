<?php

require_once PATH_THIRD.'mx_calc/addon.setup.php';

if (!class_exists('EvalMath')) {
    require_once PATH_THIRD.'mx_calc/libraries/evalmath.class.php';
}

/**
 *  MX Calculator Class for ExpressionEngine2.
 *
 * @category Plugins
 *
 * @author    Max Lazar <max@eec.ms>
 */
$plugin_info = array(
    'pi_name' => MX_CALC_NAME,
    'pi_version' => MX_CALC_VERSION,
    'pi_author_url' => MX_CALC_DOCS,
    'pi_description' => MX_CALC_DESCRIPTION,
    'pi_usage' => mx_calc::usage(),
);

class Mx_calc
{
    public $return_data = '';

    public function __construct($tagdata = '')
    {
        $result     = false;
        $tagdata    = (isset(ee()->TMPL->tagdata)) ? ee()->TMPL->tagdata : false;
        $expression = (!ee()->TMPL->fetch_param('expression')) ? '' : ee()->TMPL->fetch_param('expression');
        $debug      = (!ee()->TMPL->fetch_param('debug')) ? '' : ee()->TMPL->fetch_param('debug');

        if (!empty($expression)) {
            $m = new EvalMath();

            $m->suppress_errors = $debug == 'on' ?  false : true;

            $result[0]['calc_result'] = $m->evaluate($expression);

            if ($debug == 'yes') {
                $result[0]['debug']  = $m->last_error;
            }

            if (!empty($tagdata)) {
                $conds['calc_result'] = $result;
                $tagdata              = ee()->functions->prep_conditionals($tagdata, $conds);
                $result               = ee()->TMPL->parse_variables($tagdata, $result);
            } else {
                $result = $result[0]['calc_result'];
            }

            return $this->return_data =  $result;
        }

        return false;
    }

    // ----------------------------------------
    //  Plugin Usage
    // ----------------------------------------

    public static function usage()
    {
        // for performance only load README if inside control panel
        return REQ === 'CP' ? file_get_contents(dirname(__FILE__).'/README.md') : null;
    }

    /* END */
}

/* End of file pi.mx_calc.php */
/* Location: ./system/user/addons/mx_calc/pi.mx_calc.php */

<?php

require_once PATH_THIRD . 'mx_calc/config.php';

if ( ! class_exists( 'EvalMath' ) ) {
    require_once PATH_THIRD.'mx_calc/libraries/evalmath.class.php';
}


/**
 *  MX Calculator Class for ExpressionEngine2
 *
 * @package  ExpressionEngine
 * @subpackage Plugins
 * @category Plugins
 * @author    Max Lazar <max@eec.ms>
 */

$plugin_info = array(
    'pi_name' => MX_CALC_NAME,
    'pi_version' => MX_CALC_VER,
    'pi_author' => MX_CALC_AUTHOR,
    'pi_author_url' => MX_CALC_DOCS,
    'pi_description' => MX_CALC_DESC,
    'pi_usage' => mx_calc::usage()
);


class Mx_calc {

    var $return_data="";


    function Mx_calc() {

        $result = false;

        $tagdata =( isset( ee()->TMPL->tagdata ) ) ? ee()->TMPL->tagdata : false;

        $expression = ( !ee()->TMPL->fetch_param( 'expression' ) ) ? '' : ee()->TMPL->fetch_param( 'expression' );

        if ( !empty( $expression ) ) {
            $m = new EvalMath;

            $m->suppress_errors = true;
            $result = $m->evaluate( $expression );
            //print $m->last_error;
            if ( !empty( $tagdata ) ) {
                $conds['calc_result'] = $result;
                $tagdata =ee()->functions->prep_conditionals( $tagdata, $conds );
                $result = ee()->TMPL->swap_var_single( 'calc_result', $result, $tagdata );
            }
        }
        return $this->return_data = $result;
    }

    // ----------------------------------------
    //  Plugin Usage
    // ----------------------------------------

    public static function usage() {
        // for performance only load README if inside control panel
        return REQ === 'CP' ? file_get_contents( dirname( __FILE__ ).'/README.md' ) : null;
    }

    /* END */
}

/* End of file pi.mx_calc.php */
/* Location: ./system/expressionengine/third_party/mx_calc/pi.mx_calc.php */

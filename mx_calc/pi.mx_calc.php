<?php

require_once PATH_THIRD . 'mx_calc/addon.setup.php';

if (!class_exists('EvalMath')) {
    require_once PATH_THIRD . 'mx_calc/libraries/evalmath.class.php';
}

/**
 *  MX Calculator Class for ExpressionEngine2.
 *
 * @category Plugins
 *
 * @author    Max Lazar <max@eec.ms>
 */
$plugin_info = array(
    'pi_name'        => MX_CALC_NAME,
    'pi_version'     => MX_CALC_VERSION,
    'pi_author_url'  => MX_CALC_DOCS,
    'pi_description' => MX_CALC_DESCRIPTION,
    'pi_usage'       => mx_calc::usage(),
);

class Mx_calc
{
    public $return_data = '';

    public function __construct($tagdata = '')
    {
        $result              = false;
        $tagdata             = (isset(ee()->TMPL->tagdata)) ? ee()->TMPL->tagdata : false;
        $param['expression'] = (!ee()->TMPL->fetch_param('expression')) ? '' : ee()->TMPL->fetch_param('expression');
        $param['debug']      = (!ee()->TMPL->fetch_param('debug')) ? '' : ee()->TMPL->fetch_param('debug');
        $param['variable']   = ee()->TMPL->fetch_param('var', false);
        $param['parsing']    = ee()->TMPL->fetch_param('parsing', false);
        $param['priority']   = ee()->TMPL->fetch_param('priority', 10);

        if ($param['parsing'] == 'late') {
            $placeholder = md5(ee()->TMPL->tagproper) . rand();

            ee()->session->cache['mx_calc']['late'][$placeholder] = array(
                'param'    => $param,
                'tagdata'  => $tagdata,
                'priority' => $param['priority']
            );

            return $this->return_data = '{' . $placeholder . '}';
            //            return $this->return_data = LD . $placeholder . RD;
        }

        if (!empty($param['expression'])) {
            return $this->return_data = self::_calc($tagdata, $param);
        }

        return false;
    }

    public function _calc($tagdata, $param)
    {
        if (!$param['variable']) {
            if (isset(ee()->session->cache['mx_calc']['var'])) {
                $param['expression'] = ee()->TMPL->parse_variables(
                    $param['expression'],
                    ee()->session->cache['mx_calc']['var']
                );
            }
            if (!isset(ee()->session->cache['mx_calc']['math'])) {
                ee()->session->cache['mx_calc']['math'] = new EvalMath();
            };

            ee()->session->cache['mx_calc']['math']->suppress_errors = $param['debug'] == 'on' ? false : true;

            $result[0]['calc_result'] = ee()->session->cache['mx_calc']['math']->evaluate($param['expression']);

            if ($param['debug'] == 'yes') {
                $result[0]['debug'] = ee()->session->cache['mx_calc']['math']->last_error;
            }

            if (!empty($tagdata)) {
                $conds['calc_result'] = $result;
                $tagdata              = ee()->functions->prep_conditionals($tagdata, $conds);
                $result               = ee()->TMPL->parse_variables($tagdata, $result);
            } else {
                $result = $result[0]['calc_result'];
            }

            return $result;
        } else {
            if (!isset(ee()->session->cache['mx_calc']['var'][0][$param['variable']])) {
                ee()->session->cache['mx_calc']['var'][0][$param['variable']] = '';
            }

            ee()->session->cache['mx_calc']['var'][0][$param['variable']] .= $param['expression'];
        }

        return false;
    }

    // ----------------------------------------
    //  Plugin Usage
    // ----------------------------------------

    public static function usage()
    {
        // for performance only load README if inside control panel
        return REQ === 'CP' ? file_get_contents(dirname(__FILE__) . '/README.md') : null;
    }

    /* END */
}

<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}


class Mx_calc_ext
{

    public $settings = [];

    public $version = MX_CALC_VERSION;

    public $defaults = [
        'late_parsing' => true
    ];

    public $config = [
    ];

    /**
     * Cp_css_js_ext constructor.
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        //   $this->config = ee()->config->item('template_post_parse');

        if (!is_array($settings)) {
            $settings = $this->defaults;
        }
        if (!is_array($this->config)) {
            $this->config = [];
        }

        $this->settings = array_replace($settings, $this->config);
    }

    /**
     * @param $template
     * @param $sub
     * @param $site_id
     */
    public function template_post_parse($final_template, $is_partial, $site_id)
    {
        if (isset(ee()->extensions->last_call) && ee()->extensions->last_call) {
            $final_template = ee()->extensions->last_call;
        }

        if(isset(ee()->session->cache['mx_calc']['late'])) {
            $var = [];
            foreach (ee()->session->cache['mx_calc']['late'] as $index => $tag) {
               $var[0][$index] = self::_calc($tag['tagdata'], $tag['param']);
            }

            $final_template = ee()->TMPL->parse_variables($final_template, $var);
        }

        return $final_template;
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

            ee()->session->cache['mx_calc']['math']->suppress_errors = $param['debug'] == 'on' ? false : true;

            $result[0]['calc_result'] = ee()->session->cache['mx_calc']['math']->evaluate($param['expression']);

            if ($param['debug'] == 'yes') {
                $result[0]['debug'] = $m->last_error;
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

    /**
     * [activate_extension description]
     * @return [type] [description]
     */
    public function activate_extension()
    {
        $this->settings = $this->initializeSettings();

        $data = [
            [
                'class'    => __CLASS__,
                'method'   => 'template_post_parse',
                'hook'     => 'template_post_parse',
                'settings' => serialize($this->settings),
                'priority' => 10,
                'version'  => $this->version,
                'enabled'  => 'y'
            ]
        ];

        foreach ($data as $hook) {
            ee()->db->insert('extensions', $hook);
        }
    }


    /**
     * [disable_extension description]
     * @return [type] [description]
     */
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    /**
     * [update_extension description]
     * @param string $current [description]
     * @return [type]          [description]
     */
    public function update_extension($current = '')
    {
        // UPDATE HOOKS
        return true;
    }


    // --------------------------------
    //  Settings
    // --------------------------------

    public function settings()
    {
        $settings = array();

        return $settings;
    }

    /**
     * Settings Form
     *
     * @param Array   Settings
     * @return  void
     */
    function settings_form($current)
    {
        $name = 'mx_calc';

        if ($current == '') {
            $current = array();
        }

        $values = array_replace($this->defaults, $this->settings);

        $vars = array(
            'base_url'              => ee('CP/URL')->make('addons/settings/' . $name . '/save'),
            'cp_page_title'         => lang('addon_title'),
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'alerts_name'           => '',
            'sections'              => array(array())
        );

        $vars['sections'] = array(
            array(
                array(
                    'title'  => lang('late_parsing_enable'),
                    'fields' => array(
                        'late_parsing' => array(
                            'type'     => 'toggle',
                            'value'    => $values['late_parsing'],
                            'required' => false
                        )
                    )
                )
            )
        );


        if (version_compare(APP_VER, '6.0.0', '<')) {
        }

        return ee('View')->make('mx_calc:index')->render($vars);
    }

    /**
     * Save Settings
     *
     * This function provides a little extra processing and validation
     * than the generic settings form.
     *
     * @return void
     */
    function save_settings()
    {
        if (empty($_POST)) {
            show_error(lang('unauthorized_access'));
        }

        ee()->lang->loadfile('mx_calc');

        ee('CP/Alert')->makeInline('mx_calc_save')
            ->asSuccess()
            ->withTitle(lang('message_success'))
            ->addToBody(lang('preferences_updated'))
            ->defer();

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', array('settings' => serialize($_POST)));

        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/mx_calc'));
    }


    /**
     * [initializeSettings description]
     * @return [type] [description]
     */
    private function initializeSettings()
    {
        // Set up app settings
        $settingData = [
            'late_parsing'  => false
        ];

        return serialize($settingData);
    }

}

<?php

defined('ABSPATH') or die();

class FDC_AJAX
{
    public function __construct()
    {
        add_action('wp_ajax_fdc_action', array($this, 'ajax'));
        add_action('wp_ajax_nopriv_fdc_action', array($this, 'ajax'));
    }

    public function ajax()
    {
        check_ajax_referer('fdc_nonce', 'check');

        if( !isset($_POST['fdcUtility']) ) {
            wp_send_json_error();
        }

        $cmd = $_POST['cmd'];
        $force = filter_var(@$_POST['force'], FILTER_VALIDATE_BOOLEAN);

        unset($_POST['fdcUtility'], $_POST['cmd'], $_POST['action'], $_POST['check'], $_POST['force']);

        switch( $cmd )
        {
            case 'delete': $this->delete($force); break;
            case 'save'  : $this->save();   break;
        }
    }

    private function delete($force = false)
    {
        if( current_user_can('manage_options') )
        {

            $entry_id = fdc_delete_entry((int)$_POST['id'], $force);

            if( is_wp_error($entry_id) || empty($entry_id) ) {
                 wp_send_json_error( apply_filters('fdc_entry_after_delete_error_response', $entry_id) );
            }

            wp_send_json_success( apply_filters('fdc_entry_after_delete_success_response', $entry_id) );
        }
    }

    private function save()
    {

        $entry_id = fdc_insert_entry();

        if( is_wp_error($entry_id) || empty($entry_id) ) {
            wp_send_json_error( apply_filters('fdc_entry_after_save_error_response', $entry_id) );
        }

        wp_send_json_success( apply_filters('fdc_entry_after_save_success_response', $entry_id) );

    }

}

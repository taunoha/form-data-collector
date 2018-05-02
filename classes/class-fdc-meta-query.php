<?php

defined('ABSPATH') or die();

class FDC_Meta_Query
{
    public function __construct()
    {

    }
}

function fdc_add_entry_meta($entry_id, $meta_key, $meta_value)
{
    global $wpdb;

    if( ! $meta_key || ! is_numeric($entry_id) ) {
        return false;
    }

    $entry_id = absint($entry_id);

    if( !$entry_id ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'fdc_entries_meta';
    $meta_key = wp_unslash($meta_key);
    $meta_value = maybe_serialize($meta_value);

    $result = $wpdb->insert($table_name , array(
            'entry_id' => $entry_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        )
    );

    if( !$result ) {
        return false;
    }

    $query = new FDC_Query();
    $query->update( array('ID' => $entry_id) );

    wp_cache_delete($entry_id, 'fdc_entry_metadata');

    return (int) $wpdb->insert_id;
}

function fdc_update_entry_meta($entry_id, $meta_key, $meta_value)
{
    global $wpdb;

    if( ! $entry_id || ! is_numeric($entry_id) ) {
        return false;
    }

    $entry_id = absint($entry_id);

    if( !$entry_id ) {
        return false;
    }

    $table_meta_name = $wpdb->prefix . 'fdc_entries_meta';
    $meta_key = wp_unslash($meta_key);
    $meta_value = maybe_serialize($meta_value);

    $meta_id = $wpdb->get_col( $wpdb->prepare("SELECT meta_id FROM {$table_meta_name} WHERE meta_key = '%s' AND entry_id = %d", $meta_key, $entry_id) );

    if( empty($meta_id) ) {
        return fdc_add_entry_meta($entry_id, $meta_key, $meta_value);
    }

    $data  = compact('meta_value');
    $where = array('entry_id' => $entry_id, 'meta_key' => $meta_key);
    $result = $wpdb->update($table_meta_name, $data, $where);

    if( !$result ) {
        return false;
    }

    $query = new FDC_Query();
    $query->update( array('ID' => $entry_id) );

    wp_cache_delete($entry_id, 'fdc_entry_metadata');

    return (int) $wpdb->insert_id;
}

function fdc_get_entry_meta($entry_id, $meta_key = '')
{
    global $wpdb;

    if( ! is_numeric($entry_id) ) {
        return false;
    }

    $entry_id = absint($entry_id);

    if( !$entry_id ) {
        return false;
    }

    $table_meta_name = $wpdb->prefix . 'fdc_entries_meta';
    $meta_values = wp_cache_get($entry_id, 'fdc_entry_metadata');

    if( false === $meta_values )
    {
        $meta_values = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value FROM {$table_meta_name} WHERE entry_id = %d", $entry_id) );
        $data = wp_list_pluck($meta_values, 'meta_value', 'meta_key');
        $meta_values = array_map('maybe_unserialize', $data);
        wp_cache_set($entry_id, $meta_values, 'fdc_entry_metadata');
    }

    if( empty($meta_values) ) {
        return array();
    }

    if( empty($meta_key) ) {
        return $meta_values;
    }

    if( !empty($meta_key) && isset($meta_values[$meta_key]) ) {
        return $meta_values[$meta_key];
    }
}

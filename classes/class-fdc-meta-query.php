<?php

defined('ABSPATH') or die();

class FDC_Meta_Query extends WP_Meta_Query
{
    public function get_sql( $type, $primary_table, $primary_id_column, $context = null )
    {
        global $wpdb;

        $this->table_aliases = array();

        $this->meta_table = LD_WP_TABLE_PREFIX . 'fdc_entries_meta';
        $this->meta_id_column = sanitize_key($type . '_id');

        $this->primary_table = $primary_table;
        $this->primary_id_column = $primary_id_column;

        $sql = $this->get_sql_clauses();

        if( false !== strpos($sql['join'], 'LEFT JOIN') ) {
            $sql['join'] = str_replace('INNER JOIN', 'LEFT JOIN', $sql['join']);
        }

        return apply_filters_ref_array('get_meta_sql', array($sql, $this->queries, $type, $primary_table, $primary_id_column, $context));
    }
}

function fdc_get_entries_meta_query_clauses( $query_args = array() )
{
    global $wpdb;

    $meta_query = new FDC_Meta_Query();
    $meta_query->parse_query_vars($query_args);

    return $meta_query->get_sql('entry', LD_WP_TABLE_PREFIX . 'fdc_entries', 'ID', null);
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

    $table_name = LD_WP_TABLE_PREFIX . 'fdc_entries_meta';
    $meta_key = wp_unslash($meta_key);
    $meta_value = wp_unslash($meta_value);
    $meta_value = sanitize_meta($meta_key, $meta_value, 'fdc');

    $result = $wpdb->insert($table_name , array(
            'entry_id' => $entry_id,
            'meta_key' => $meta_key,
            'meta_value' => maybe_serialize($meta_value)
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

    $table_meta_name = LD_WP_TABLE_PREFIX . 'fdc_entries_meta';

    $meta_id = $wpdb->get_col( $wpdb->prepare("SELECT meta_id FROM {$table_meta_name} WHERE meta_key = '%s' AND entry_id = %d", $meta_key, $entry_id) );

    if( empty($meta_id) ) {
        return fdc_add_entry_meta($entry_id, $meta_key, $meta_value);
    }

    $meta_key = wp_unslash($meta_key);
    $meta_value = wp_unslash($meta_value);
    $meta_value = sanitize_meta($meta_key, $meta_value, 'fdc');
    $meta_value = maybe_serialize($meta_value);

    $data = compact('meta_value');
    $where = array('entry_id' => $entry_id, 'meta_key' => $meta_key);
    $result = $wpdb->update($table_meta_name, $data, $where);

    if( false === $result ) {
        return false;
    }

    $query = new FDC_Query();
    $query->update( array('ID' => $entry_id) );

    wp_cache_delete($entry_id, 'fdc_entry_metadata');

    return (int) $wpdb->insert_id;
}

function fdc_get_entry_meta($entry_id, $meta_key = '', $context = null)
{
    global $wpdb;

    if( ! is_numeric($entry_id) ) {
        return false;
    }

    $entry_id = absint($entry_id);

    if( !$entry_id ) {
        return false;
    }

    $table_name = LD_WP_TABLE_PREFIX . 'fdc_entries';
    $table_meta_name = LD_WP_TABLE_PREFIX . 'fdc_entries_meta';
    $meta_values = wp_cache_get($entry_id, 'fdc_entry_metadata');

    if( false === $meta_values )
    {
        $exclude_deleted = "AND entry_id NOT IN ( SELECT {$table_name}.ID FROM $table_name WHERE ID = entry_id AND entry_deleted IN ('yes') )";

        if( $context instanceof FDC_Query && 'yes' == strtolower($context->query_vars['entry_deleted']) ) {
            $exclude_deleted = '';
        }

        $meta_values = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value FROM {$table_meta_name} WHERE entry_id = %d {$exclude_deleted}", $entry_id), ARRAY_A);
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

    if( !empty($meta_key) ) {
        return '';
    } else {
        return array();
    }
}

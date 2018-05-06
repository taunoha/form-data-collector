<?php
defined('ABSPATH') or die();

/**
 * Form Data Collector settings
 * https://wordpress.org/plugins/form-data-collector/
 *
 * @since 2.0.0
 *
 */

function fdc_entry_labels($key = '')
{
    $labels = array(
        'formID'    => 'Form ID',
        'fromURL'   => 'From URL',
        'name'      => 'Name',
        'email'     => 'Email',
        'file'      => 'Attachment',
        'files'     => 'Attachments'
    );

    if( !empty($key) )
    {
        if( isset($labels[$key]) ) {
            return $labels[$key];
        } else {
            return $key;
        }
    }

    return $labels;
}

function fdc_pre_save_entry_data_callback($data)
{
    foreach( $data as $key => $value )
    {
        switch( $key )
        {
            case 'email':
                $data[$key]= sanitize_email($value);
                break;
            default:
                $data[$key]= ( is_array($value) ) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
        }
    }

    return $data;
}
add_filter('fdc_pre_save_entry_data', 'fdc_pre_save_entry_data_callback');

function fdc_allowed_entry_fields_callback($allowed_fields, $data)
{
    if( !isset($data['honeypot']) ) {
        return null;
    }

    if( !empty($data['honeypot']) ) {
        return null;
    }

    $keys = array_keys(fdc_entry_labels());

    return $keys;
}
add_filter('fdc_allowed_entry_fields', 'fdc_allowed_entry_fields_callback', 10, 2);

function fdc_manage_entries_columns_callback($columns)
{
    $first = array_slice($columns, 0, 1);
    $last = array_slice($columns, 1, count($columns));

    $custom = array(
        'formID' => 'Form ID'
    );

    return array_merge($first, $custom, $last);
}
add_filter('fdc_manage_entries_columns', 'fdc_manage_entries_columns_callback');

function fdc_manage_entries_custom_column_callback($item, $column_name)
{
    switch($column_name)
    {
        case 'formID':
            echo $item['meta']['formID'] ?? '';
            break;
    }
}
add_action('fdc_manage_entries_custom_column', 'fdc_manage_entries_custom_column_callback', 10, 2);

function fdc_restrict_manage_entries_callback()
{
    echo '<select name="formID">';
    echo '<option value="">All Forms</option>';
    echo '<option value="feedback" ' . selected(@$_GET['formID'], 'feedback', false) . '>Feedback</option>';
    echo '</select>';
}
add_action('fdc_restrict_manage_entries', 'fdc_restrict_manage_entries_callback');

function fdc_pre_get_entries_callback($query)
{

    if( isset($_GET['formID']) && !empty($_GET['formID']) )
    {
        $query->set('meta_query', array(
            array(
                'key' => 'formID',
                'value' => sanitize_text_field($_GET['formID'])
            )
        ));
    }
}
add_action('fdc_pre_get_entries', 'fdc_pre_get_entries_callback');

function fdc_thickbox_iframe_content_callback($entry_id, $entry_data)
{
    $data = $entry_data;

    if( !isset($data['meta']) ) {
        echo 'No metadata found for Entry ' . $entry_id;
        return;
    }

    echo '<table class="wp-list-table widefat striped"><tbody>';

    foreach( $data['meta'] as $meta_key => $meta_value )
    {
        echo '<tr>';
        printf('<td style="width: 180px">%s</td>', fdc_entry_labels($meta_key));
        printf('<td>%s</td>', maybe_serialize($meta_value));
        echo '</tr>';
    }

    echo '</tbody></table>';
}
add_action('fdc_thickbox_iframe_content', 'fdc_thickbox_iframe_content_callback', 10, 2);

function fdc_after_entry_inserted_callback($entry_id)
{
    $meta = fdc_get_entry_meta($entry_id);
}
add_action('fdc_after_entry_inserted', 'fdc_after_entry_inserted_callback', 10);

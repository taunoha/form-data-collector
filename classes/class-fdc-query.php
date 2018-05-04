<?php

defined('ABSPATH') or die();

class FDC_Query
{
    public $entries;

    public function __construct( $args = array() )
    {
        if( !empty($args) )
        {
            return $this->entries = $this->get($args);
        }
    }

    public function insert($args = array())
    {
        global $wpdb;

        if( false === $args ) {
            return 0;
        }

        $defaults = array(
            'ID' => 0,
            'blog_id' => '',
            'ip' => '',
            'entry_date' => '',
            'entry_modified_date' => '',
            'entry_deleted' => ''
        );
        $args = wp_parse_args($args, $defaults);

        $entry_date = ( !empty($args['entry_date']) ) ? $args['entry_date'] : current_time('mysql');
        $blog_id = ( !empty($args['blog_id']) ) ? $args['blog_id'] : get_current_blog_id();
        $entry_modified_date = current_time('mysql');
        $entry_deleted = $args['entry_deleted'];

        $data = compact('entry_date', 'blog_id', 'entry_modified_date', 'entry_deleted');

        // Update
        //
        if( !empty($args['ID']) )
        {
            if ( false === $wpdb->update($wpdb->prefix . 'fdc_entries', $data, array('ID' => (int) $args['ID']) ) ) {
                return 0;
            }

            array_walk_recursive($data, function( &$data ) {
                $data = maybe_unserialize($data);
            });

            return $data;
        }

        // Insert new
        //

        $data['ip'] = fdc_get_real_ip();

        if ( false === $wpdb->insert($wpdb->prefix . 'fdc_entries', $data) ) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }


    public function update($data = array())
    {
        if( isset($data['ID']) )
        {
            $entry = $this->get( array('ID' => (int) $data['ID']) );

            if( isset($entry[0]) )
            {
                $update = array_merge($entry[0], $data);
                wp_cache_delete($data['ID'], 'fdc_entry');

                return $this->insert($update);
            }

        } else {
            return $this->insert($data);
        }

        return 0;
    }

    public function delete($entry_id = 0)
    {
        global $wpdb;

        if( !empty($entry_id) ) {
            return $this->update( array('ID' => (int) $entry_id, 'entry_deleted' => 'yes') );
        }

        return false;
    }


    public function search($s = '')
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'fdc_entries';
        $table_meta_name = $wpdb->prefix . 'fdc_entries_meta';
        $phrase = esc_sql($s);

        $sql = apply_filters('fdc_entries_search_sql', "SELECT DISTINCT {$table_name}.ID FROM {$table_name} LEFT JOIN {$table_meta_name} ON {$table_name}.ID = {$table_meta_name}.entry_id WHERE {$table_meta_name}.meta_value LIKE '%{$phrase}%' ORDER BY entry_date DESC");
        $results = $wpdb->get_results($sql, ARRAY_A);

        if( !empty($results) )
        {
            $ids = wp_list_pluck($results, 'ID');
            return $this->get(array('entry__in' => $ids));
        }

        return 0;
    }

    public function get($args = array())
    {
        global $wpdb;

        $defaults = array(
            'ID' => '*',
            'entry__in' => array(),
            'blog_id' => get_current_blog_id(),
            'entry_date_after' => '*',
            'entry_deleted' => '',
            'entries_per_page' => '',
            's' => ''
        );
        $args = wp_parse_args($args, $defaults);

        if( !empty($args['s']) ) {
            return $this->search($args['s']);
        }

        $limit = '';
        $where = '';
        $wheres = array();

        if( intval($args['ID']) ) {
            $wheres[] = 'AND ID = ' . (int) $args['ID'];
        }

        if( !empty($args['blog_id']) && get_current_blog_id() != $args['blog_id'] ) {
            $wheres[] = 'AND blog_id = "' . $args['blog_id'] . '"';
        }

        if( !empty($args['entry__in']) && is_array($args['entry__in']) )
        {
            $ids = array_map('absint', $args['entry__in']);
            if( !empty($ids) ) {
                $wheres[] = 'AND ID IN (' . implode(',', $ids) . ')';
            }
        }

        if( !empty($args['entry_date_after']) && '*' != $args['entry_date_after'] )
        {
            $datetime = @new DateTime($args['entry_date_after']);

            if( $datetime instanceOf DateTime ) {
                $wheres[] = 'AND entry_date_after >= "' . $datetime->format('Y-m-01 00:00') . '"';
            }
        }

        if( !empty($args['entries_per_page']) ) {
            $limit = 'LIMIT ' . $args['entries_per_page'];
        }

        if( 'yes' == $args['entry_deleted'] ) {
            $wheres[] = 'AND entry_deleted IN ("yes")';
        } else {
            $wheres[] = 'AND entry_deleted NOT IN ("yes")';
        }

        $where = implode(' ', $wheres);
        $sql = apply_filters('fdc_entries_request_sql', "SELECT * FROM {$wpdb->prefix}fdc_entries WHERE 1=1 {$where} ORDER BY entry_date DESC {$limit}");
        $results = $wpdb->get_results($sql, ARRAY_A);

        if( $results )
        {
            array_walk_recursive($results, function( &$results ) {
                $results = maybe_unserialize($results);
            });

            foreach( $results as $key => $result ) {
                $results[$key]['meta']= fdc_get_entry_meta($result['ID']);
            }

            return $results;
        }

        return 0;
    }

}


function fdc_insert_entry($data = array())
{
    if( empty($data) )
    {
        if( isset($_POST) ) {
            $data = $_POST;
        } else {
            return 0;
        }
    }

    if( isset($data['data']) ) {
        $data = $data['data'];
    }

    $allowed_fields = apply_filters('fdc_allowed_entry_fields', null, $data);

    if( null == $allowed_fields ) {
        return 0;
    }

    $data = array_intersect_key($data, array_flip($allowed_fields));

    if( has_filter('fdc_pre_save_entry_post_data') ) {

        $data = apply_filters('fdc_pre_save_entry_post_data', $data);

    } else {

        foreach( $data as $key => $value ) {
            $data[$key]= ( is_array($value) ) ? array_map('sanitize_textarea_field', $value) : sanitize_textarea_field($value);
        }

    }


    if( empty($data) ) {
        return 0;
    }

    if( is_wp_error($data) ) {
        return $data;
    }

    $query = new FDC_Query();
    $entry_id = $query->insert();

    if( !empty($entry_id) )
    {
        foreach( $data as $meta_key => $meta_value ) {
            fdc_add_entry_meta($entry_id, $meta_key, $meta_value);
        }

        if( isset($_FILES) && !empty($_FILES) )
        {
            do_action('fdc_before_upload_file_handler', $entry_id, $_FILES);

            if( false === apply_filters('fdc_override_upload_handler', false) )
            {
                foreach( $_FILES as $key => $values )
                {
                    if( !in_array($key, $allowed_fields) ) {
                        continue;
                    }

                    $files = fdc_diverse_array($_FILES[$key]);

                    if( !empty($files) )
                    {
                        $attachments = array();

                        foreach( $files as $file )
                        {
                            $file = fdc_handle_upload_file($file);

                            if( !isset($file['error']) ) {
                               $attachments[]= $file;
                            } else {
                                $attachments[]= $file['error'];
                            }
                        }

                        fdc_add_entry_meta($entry_id, $key, $attachments);

                    } else {

                        $file = fdc_handle_upload_file($_FILES[$key]);

                        if( !isset($file['error']) ) {
                            fdc_add_entry_meta($entry_id, $key, $file);
                        } else {
                            fdc_add_entry_meta($entry_id, $key, $file['error']);
                        }
                    }
                }
            }
        }

        do_action('fdc_after_entry_inserted', $entry_id);

        return $entry_id;
    }

    return 0;
}

function fdc_get_entries($args = array())
{
    $defaults = array(
        'ID' => '*'
    );
    $args = wp_parse_args($args, $defaults);
    $query = new FDC_Query($args);

    return empty($query->entries) ? array() : $query->entries;
}

function fdc_delete_entry($entry_id)
{
    $query = new FDC_Query();
    $entry = $query->delete($entry_id);

    if( empty($entry) ) {
        return 0;
    }

    do_action('fdc_after_entry_deleted', $entry_id, $entry);

    return $entry_id;
}

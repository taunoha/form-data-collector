<?php

defined('ABSPATH') or die();

class FDC_Query
{
    public $entries = array();
    public $query_vars = array();

    public function __construct( $args = array() )
    {
        if( !empty($args) )
        {
            return $this->entries = $this->get($args);
        }
    }

    public function set($query_var, $value) {
        $this->query_vars[$query_var] = $value;
    }

    public function insert($args = array())
    {
        global $wpdb;

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
        return $this->get( array('s' => $s) );
    }

    public function get($args = array())
    {
        global $wpdb;

        $defaults = array(
            'ID' => '*',
            'entry__in' => array(),
            'blog_id' => get_current_blog_id(),
            'entry_deleted' => '',
            'entries_per_page' => -1,
            'offset' => 0,
            'meta_query' => array(),
            'date_query' => array(),
            's' => ''
        );
        $this->query_vars = wp_parse_args($args, $defaults);

        do_action_ref_array('fdc_pre_get_entries', array(&$this));

        $args = &$this->query_vars;

        if( !empty($args['s']) )
        {
            $args['meta_query']= array_merge($args['meta_query'], array(
                array(
                    'value' => sanitize_text_field($args['s']),
                    'compare' => 'LIKE'
                )
            ));
        }

        $limit = '';
        $offset = '';
        $where = '';
        $join = '';
        $wheres = array();
        $joins = array();

        if( !empty($args['meta_query']) )
        {
            $meta_query_clauses = fdc_get_entries_meta_query_clauses( array('meta_query' => $args['meta_query']) );

            if( isset($meta_query_clauses['join']) ) {
                $joins[] = $meta_query_clauses['join'];
            }

            if( isset($meta_query_clauses['where']) ) {
                $wheres[] = $meta_query_clauses['where'];
            }

            unset($meta_query_clauses);

        }

        if( !empty($args['date_query']) )
        {
            $date_query = new WP_Date_Query( array($args['date_query']), $wpdb->prefix . 'fdc_entries.entry_date' );
            $wheres[]= $date_query->get_sql();

            unset($date_query);
        }

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

        if( $args['entries_per_page'] > -1 ) {
            $limit = 'LIMIT ' . absint($args['entries_per_page']);
        }

        if( absint($args['offset']) > 0 ) {
            $offset = 'OFFSET ' . absint($args['offset']);
        }

        if( 'yes' == strtolower($args['entry_deleted']) ) {
            $wheres[] = 'AND entry_deleted IN ("yes")';
        } else {
            $wheres[] = 'AND entry_deleted NOT IN ("yes")';
        }

        $join = implode(' ', $joins);
        $where = implode(' ', $wheres);
        $sql = apply_filters('fdc_entries_request_sql', "SELECT {$wpdb->prefix}fdc_entries.* FROM {$wpdb->prefix}fdc_entries {$join} WHERE 1=1 {$where} ORDER BY entry_date DESC {$limit} {$offset}");
        $results = $wpdb->get_results($sql, ARRAY_A);

        if( $results )
        {
            array_walk_recursive($results, function( &$results ) {
                $results = maybe_unserialize($results);
            });

            foreach( $results as $key => $result )
            {
                $metadata = fdc_get_entry_meta($result['ID'], null, $this);
                $results[$key]['meta']= fdc_preg_grep_keys('/^[^\_]/', $metadata);
            }

            return $results;
        }

        return 0;
    }

}

/**
 * Insert an entry into the database
 *
 * @since 2.2.0                     Improved error handling
 * @since 2.0.0
 *
 * @param array $data               Data to store in database or $_POST will be used.
 *
 * @return int|WP_Error             Return inserted entry ID or WP_Error.
 *
 */
function fdc_insert_entry($data = array())
{
    if( empty($data) )
    {
        if( isset($_POST) ) {
            $data = $_POST;
        } else {
            return new WP_Error('data-missing', __('Nothing to store in database.', 'fdc'));
        }
    }

    if( isset($data['data']) ) {
        $data = $data['data'];
    }

    $allowed_fields = apply_filters('fdc_allowed_entry_fields', null, $data);

    if( null === $allowed_fields ) {
        return new WP_Error('allowed-fields-are-missing', __('No allowed fields found', 'fdc'));
    }

    $data = array_intersect_key($data, array_flip($allowed_fields));

    if( has_filter('fdc_pre_save_entry_post_data') ) {
        $data = apply_filters('fdc_pre_save_entry_post_data', $data);

    } else if( has_filter('fdc_pre_save_entry_data') ) {
        /**
         * Filter entry data before storing in database
         *
         * @since 2.2.0                     Added an option to return WP_Error
         * @since 2.0.0
         *
         * @param array                     Data to be filtered
         *
         * @return array|null|WP_Error      Return filtered data, NULL or WP_Error.
         *                                  By returning WP_Error you can add validation errors.
         *
         */
        $data = apply_filters('fdc_pre_save_entry_data', $data);

    } else {

        foreach( $data as $key => $value ) {
            $data[$key]= ( is_array($value) ) ? array_map('sanitize_textarea_field', $value) : sanitize_textarea_field($value);
        }

    }

    if( is_wp_error($data) ) {
        return $data;
    }

    if( empty($data) ) {
        return new WP_Error('data-missing', __('Nothing to store in database.', 'fdc'));
    }

    $query = new FDC_Query();
    $entry_id = $query->insert();

    if( !empty($entry_id) )
    {
        foreach( $data as $meta_key => $meta_value )
        {
            if( in_array($meta_key, $allowed_fields) ) {
                fdc_add_entry_meta($entry_id, $meta_key, $meta_value);
            }
        }

        if( isset($_FILES) && !empty($_FILES) )
        {
            do_action('fdc_before_upload_file_handler', $entry_id, $_FILES);

            if( false === apply_filters('fdc_override_upload_handler', false) )
            {
                $_entry_attachments = array();

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
                               $_entry_attachments[]= $file['file'];
                            } else {
                                $attachments[]= $file['error'];
                            }
                        }

                        fdc_add_entry_meta($entry_id, $key, $attachments);

                    } else {

                        $file = fdc_handle_upload_file($_FILES[$key]);

                        if( !isset($file['error']) ) {
                            fdc_add_entry_meta($entry_id, $key, $file);
                            $_entry_attachments[]= $file['file'];
                        } else {
                            fdc_add_entry_meta($entry_id, $key, $file['error']);
                        }
                    }
                }

                if( !empty($_entry_attachments) ) {
                    fdc_add_entry_meta($entry_id, '_entry_attachments', $_entry_attachments);
                }
            }
        }

        do_action('fdc_after_entry_inserted', $entry_id);

        return $entry_id;
    }

    return new WP_Error('data-insertion-error', __('Unknown error occurred. No entry stored in database.', 'fdc'));
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

/**
 * Filter entry data before storing in database
 *
 * @since 2.2.0             Added an option to force delete entry and all its data
 * @since 2.2.0             Return WP_Error if some error occurred.
 * @since 2.0.0
 *
 * @param int
 * @param bool  $force      An option to force delete the entry and all its data. Default is 'false';
 *
 * @return int|WP_Error     Return deleted entry ID.
 *                          WP_Error on error.
 *
 */
function fdc_delete_entry($entry_id, $force = false)
{
    global $wpdb;

    $entry = fdc_get_entries(array('ID' => (int) $entry_id, 'entries_per_page' => 1));

    // Force Delete an Entry
    //
    if( true === $force )
    {
        $attachments = fdc_get_entry_meta($entry_id, '_entry_attachments');

        if( ! $wpdb->delete($wpdb->prefix . 'fdc_entries', array('ID' => (int) $entry_id)) ) {
            return new WP_Error('data-force-deletion-error', __('Unknown error occured. The entry was not deleted.', 'fdc'));
        }

        if( ! $wpdb->delete($wpdb->prefix . 'fdc_entries_meta', array('entry_id' => (int) $entry_id)) ) {
            return new WP_Error('data-force-deletion-error', __("Unknown error occured. The entry's metadata was not deleted.", 'fdc'));
        }

        if( !empty($attachments) )
        {
            foreach( $attachments as $attachment )
            {
                @unlink($attachment);
            }
        }

        do_action('fdc_after_entry_deleted', $entry_id, @$entry[0]);
        wp_cache_delete($entry_id, 'fdc_entry_metadata');

        return (int) $entry_id;
    }

    // Delete an Entry
    //
    $query = new FDC_Query();

    if( ! $query->delete($entry_id) ) {
        return new WP_Error('data-deletion-error', __('Unknown error occured. The entry was not deleted.', 'fdc'));
    }

    do_action('fdc_after_entry_deleted', $entry_id, @$entry[0]);
    wp_cache_delete($entry_id, 'fdc_entry_metadata');

    return (int) $entry_id;
}

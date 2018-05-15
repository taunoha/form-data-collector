<?php

defined('ABSPATH') or die();

class FDC_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;
        parent::__construct( array(
            'singular'  => 'entry',
            'plural'    => 'entries',
            'ajax'      => false
        ) );
    }

    function display_tablenav($which)
    {
        echo '<div class="tablenav ' . esc_attr( $which ) . '">';

        if ( $this->has_items() )
        {
            echo '<div class="alignleft actions bulkactions">';
            $this->bulk_actions($which);
            echo '</div>';
        }

        $this->extra_tablenav($which);
        $this->pagination($which);
        echo '</div>';
    }

    function extra_tablenav($which)
    {
        if ( 'top' === $which )
        {
            echo '<div class="alignleft actions">';

            ob_start();
            do_action('fdc_restrict_manage_entries');
            $output = ob_get_clean();

            if( !empty($output) )
            {
                echo $output;
                submit_button( __('Filter', 'fdc'), 'button', 'fdc_filter_actions', false, array('id' => 'fdc-filter-actions'));
            }

            echo '</div>';
        }
    }

    function column_default($item, $column_name)
    {
        $url = add_query_arg( array(
            'action'    => 'fdc_entry_modal',
            'entry_id'  =>  intval($item['ID']),
            'TB_iframe' => 'true',
            'width'     => '800',
            'height'    => '600'
        ), admin_url('admin.php') );

        $actions = array(
            'view'      => sprintf('<a href="%s" class="thickbox">%s</a>', esc_url($url), __('View', 'fdc')),
            'delete'    => sprintf('<a href="javascript:void(0);" data-id="%d" data-action="delete">%s</a>', $item['ID'], __('Delete', 'fdc'))
        );

        switch($column_name)
        {
            case 'entry_id':
                printf('<a href="%s" class="thickbox">%d</a>',  esc_url($url), $item['ID']);
                echo $this->row_actions($actions);
            break;
            case 'entry_date':
                echo date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['entry_date']) );
            break;
            case 'entry_modified_date':
                echo date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['entry_modified_date']) );
            break;
            case 'ip':
                printf('<a href="http://ipinfo.io/%s" target="_blank">%s</a>', $item['ip'],$item['ip']);
            break;
            default:
                do_action('fdc_manage_entries_custom_column', $item, $column_name);
                break;
        }
    }

    function get_columns()
    {
        return apply_filters('fdc_manage_entries_columns', array(
            'entry_id' => esc_attr__('Entry ID', 'fdc'),
            'entry_date' => esc_attr__('Entry Date', 'fdc'),
            'entry_modified_date' => esc_attr__('Entry Modified', 'fdc'),
            'ip' => esc_attr__('IP', 'fdc'),
        ));
    }

    function prepare_items()
    {
        $per_page = 100;
        $columns = $this->get_columns();
        $hidden = array();
        $this->_column_headers = array($columns, $hidden);

        if( isset($_REQUEST['s']) && !empty($_REQUEST['s']) ) {
            $data = fdc_get_entries(array('s' => $_REQUEST['s']));
        } else {
            $data = fdc_get_entries();
        }

        if( $data )
        {
            $current_page = $this->get_pagenum();
            $total_items = count($data);
            $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
            $this->items = $data;

            $this->set_pagination_args( array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items/$per_page)
            ) );
        }
    }

    public function search_box($text, $input_id = 'fdc-entries-search')
    {
        if ( empty( $_REQUEST['s'] ) && !$this->has_items() ) {
            return;
        }
        ?>
        <p class="search-box">
            <input type="search" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button( $text, 'button', '', false, array('id' => 'search-submit') ); ?>
        </p>
        <?php
    }
}

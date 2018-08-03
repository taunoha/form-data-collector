<?php
/**
 *  Plugin Name: Form Data Collector
 *  Plugin URI: https://www.loomdigital.ee/
 *  Description: This plugin is a developer's tookit for collecting form data from your WordPress site
 *  Version: 2.2.2
 *  Author: LOOM Digital
 *  Author URI: https://www.loomdigital.ee/
 *  License: GPL2+
 *  Text Domain: fdc
 *
 *  Copyright 2018  LOOM Digital  (email: hi@loomdigital.ee)
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License, version 2, as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
**/

defined('ABSPATH') or die();

global $fdc_db_version;
$fdc_db_version = '1.0';

define('FDC_VERSION', '2.2.2');

class Form_Data_Collector
{
    public function __construct()
    {
        require_once(dirname( __FILE__ ) . '/fdc-utilities.php');
        require_once(dirname( __FILE__ ) . '/classes/class-fdc-query.php');
        require_once(dirname( __FILE__ ) . '/classes/class-fdc-meta-query.php');
        require_once(dirname( __FILE__ ) . '/classes/class-fdc-ajax.php');

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_init', array($this, 'privacy_policy_content'), 20);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('admin_print_styles', array($this, 'thickbox_iframe_css'));
        add_action('admin_print_styles', array($this, 'admin_css'));
        add_action('admin_footer', array($this, 'admin_footer'));
        add_action('admin_action_fdc_entry_modal', array($this, 'thickbox_iframe'));

        $_GLOBAL['formDataCollectorAjax'] = new FDC_AJAX();

    }

    public function admin_init()
    {
        if( !class_exists('WP_List_Table') ) {
            require_once('classes/class-wp-list-table.php');
        }

        require_once('classes/class-fdc-list-table.php');
    }

    public function privacy_policy_content()
    {
        /**
         * Add text for the site privacy policy page
         * https://developer.wordpress.org/plugins/privacy/suggesting-text-for-the-site-privacy-policy/
         *
         * @since 2.2.2
         */
        if( function_exists('wp_add_privacy_policy_content') ) {
            wp_add_privacy_policy_content('Form Data Collector', apply_filters('fdc_privacy_policy_content', '<p>Use <b>fdc_privacy_policy_content</b> filter hook to add content here.</p>') );
        }
    }

    public function add_admin_menu()
    {
        add_menu_page('FDC', 'FDC' . ' ' . $this->get_notification_counter(), 'manage_options', 'fdc_entries', array($this, 'entries'), 'dashicons-testimonial');
        add_submenu_page('fdc_entries', __('Entries', 'fdc'), __('Entries', 'fdc'), 'manage_options', 'fdc_entries', array($this, 'entries'));
    }

    public function admin_notices()
    {
        $notices = array();
        $screen = get_current_screen();

        if( $screen->id != 'toplevel_page_fdc_entries' ) {
            return;
        }

        if( !has_action('fdc_thickbox_iframe_content') ) {
            $notices[]= __('Before you can start using this plugin first define fields that are allowed to store in databaase. <br>Use <b>fdc_allowed_entry_fields</b> filter. How to do that look at <em>/plugins/form-data-collector/example/example-functions.php </em>');
        }

        if( !!( !has_filter('fdc_pre_save_entry_post_data') && !has_filter('fdc_pre_save_entry_data')) ) {
            $notices[]= __('Validate user input by using <b>fdc_pre_save_entry_data</b> filter.<br>By default all input fields are considered as textarea fields and will be filtered accordingly before stored in database. How to do that look at <em>/plugins/form-data-collector/example/example-functions.php </em>');
        }

        if( !empty($notices) )
        {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo implode('<br><br>', $notices);
            echo '</p></div>';
        }
    }

    public function thickbox_iframe()
    {
        define('IFRAME_REQUEST', true);

        $entry_data = array();
        $entry_id = ( isset($_GET['entry_id']) ) ? (int) $_GET['entry_id'] : 0;

        iframe_header();

        if( !empty($entry_id) )
        {
            $query = new FDC_Query(array(
                'ID' => $entry_id,
                'entries_per_page' => 1
            ));

            if( isset($query->entries[0]) )
            {
                echo '<div class="wrap">';

                if( has_action('fdc_thickbox_iframe_content') ) {
                    do_action('fdc_thickbox_iframe_content', $entry_id, $query->entries[0]);
                } else {
                    printf('<div class="notice notice-warning"><p>%s</p></div>', __('Please use <b>fdc_thickbox_iframe_content</b> action to add content to this modal.', 'fdc'));
                }

                echo '</div>';
            }

        } else {
            printf('<div class="notice notice-warning"><p>',__('Entry ID missing', 'fdc'));
        }

        iframe_footer();

        exit;
    }

    public function entries()
    {
        add_thickbox();

        echo '<div class="wrap">';
        printf('<h1>%s</h1>', __('Entries', 'fdc'));

        $tableList = new FDC_List_Table();
        $tableList->prepare_items();

        echo '<form id="fdc-enties-filter" method="get">';
        echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
        $tableList->search_box(__('Search', 'fdc'));
        $tableList->display();
        echo '</form>';

        echo '</div>';

        update_option('_fdc_user_visited_entries', current_time('mysql'));
    }

    public function front_scripts()
    {
        if( WP_DEBUG ) {
            wp_enqueue_script('fdc', plugins_url('/scripts/fdc-front.js' , __FILE__ ), array('jquery'), FDC_VERSION, true);
        } else {
            wp_enqueue_script('fdc', plugins_url('/scripts/fdc-front.min.js' , __FILE__ ), array('jquery'), FDC_VERSION, true);
        }

        wp_localize_script('fdc', '_fdcVars', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fdc_nonce')
            ),
            'str' => array(
                'no_file_added' => __('No file added by the user', 'fdc')
            )
        ));
    }

    public function admin_scripts()
    {
        wp_enqueue_script('jquery-ui-dialog');

        if( WP_DEBUG ) {
            wp_enqueue_script('fdc', plugins_url('/scripts/fdc-admin.js' , __FILE__ ), array('jquery', 'jquery-ui-dialog', 'wp-util'), FDC_VERSION, true);
        } else {
            wp_enqueue_script('fdc', plugins_url('/scripts/fdc-admin.min.js' , __FILE__ ), array('jquery', 'jquery-ui-dialog', 'wp-util'), FDC_VERSION, true);
        }

        wp_localize_script('fdc', '_fdcVars', array(
            'ajax' => array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fdc_nonce')
            ),
            'str' => array(
                'no_file_added' => __('No file was inserted by the user', 'fdc'),
                'delete_this_entry' => __('Delete this entry', 'fdc'),
                'cancel' => __('Cancel', 'fdc'),
                'delete_dialog_title' => __('Entry {#}', 'fdc')
            )
        ));
    }

    public function admin_css()
    {
        $screen = get_current_screen();

        if( $screen->id != 'toplevel_page_fdc_entries' ) {
            return;
        }

        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_style('fdc-admin', plugins_url('/gfx/fdc-admin-styles.css' , __FILE__ ));
    }

    public function admin_footer()
    {
        ?>
        <div id="fdc-delete-modal" class="hidden">
            <form action="<?php echo esc_url(admin_url()); ?>" method="post">
                <p><b><?php _e('Do you really want to delete this entry?', 'fdc'); ?></b></p>
                <p><label><input type="checkbox" name="fdcForceDelete"><?php _e('Force delete this entry and all its data', 'fdc'); ?></label></p>
            </form>
        </div>
        <?php
    }

    public function thickbox_iframe_css()
    {
        if( defined('IFRAME_REQUEST') )
        {
            wp_enqueue_style('fdc-iframe', plugins_url('/gfx/fdc-iframe-styles.css' , __FILE__ ));
        }
    }

    protected function get_notification_counter()
    {
        global $wpdb;

        $counter = '';
        $last_visited = get_option('_fdc_user_visited_entries', current_time('mysql'));
        $d = new DateTime($last_visited);

        if( strtotime(current_time('mysql')) < $d->modify('+15 minutes')->format('timestamp') ) {
            return '';
        }

        $count = $wpdb->get_var( "SELECT count({$wpdb->base_prefix}fdc_entries.ID) as count FROM {$wpdb->base_prefix}fdc_entries WHERE {$wpdb->base_prefix}fdc_entries.entry_date > '{$last_visited}' AND entry_deleted NOT IN ('yes')" );

        if( $count > 0 )
        {
            $notifications = sprintf(_n( '%s new entry', '%s new entries', $count, 'fdc'), $count);
            $counter = sprintf('<span class="update-plugins count-%1$d"><span class="plugin-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>', $count, $notifications);
        }

        unset($d);

        return $counter;
    }

    static function install()
    {
        global $wpdb;
        global $fdc_db_version;

        $installed_ver = get_option('fdc_db_version');

        if( $installed_ver != $fdc_db_version )
        {
            $max_index_length = 191;
            $table_name = $wpdb->base_prefix . 'fdc_entries';
            $table_meta_name = $wpdb->base_prefix . 'fdc_entries_meta';
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                    ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    blog_id bigint(2) unsigned NOT NULL default '1',
                    ip varchar(60) default NULL,
                    entry_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    entry_modified_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                    entry_deleted varchar(3) DEFAULT NULL,
                    PRIMARY KEY  (ID),
                    KEY blog_id (blog_id)
            ) $charset_collate;

            CREATE TABLE $table_meta_name (
              meta_id bigint(20) unsigned NOT NULL auto_increment,
              entry_id bigint(20) unsigned NOT NULL default '0',
              meta_key varchar(255) default NULL,
              meta_value longtext,
              PRIMARY KEY  (meta_id),
              KEY entry_id (entry_id),
              KEY meta_key (meta_key($max_index_length))
            ) $charset_collate";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            update_option('fdc_db_version', $fdc_db_version);
        }
    }
}

register_activation_hook(__FILE__, array('Form_Data_Collector', 'install'));

$_GLOBAL['formDataCollector'] = new Form_Data_Collector();

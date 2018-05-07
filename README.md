# Form Data Collector

**Contributors:** taunoh<br>
**Donate link:** https://www.loomdigital.ee<br>
**Tags:** form, email, forms, input, ajax, database<br>
**Requires at least:** 4.9<br>
**Tested up to:** 4.9.5<br>
**Stable tag:** 2.1.0<br>
**License:** GPLv2 or later<br>
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

This plugin will help you to collect and store form data.


## Description

This plugin is a developer’s toolkit for collecting form data from your WordPress site. It provides the necessary hooks and utilities for you to manage how data is stored and displayed later.

The best way to get started is to look at example-functions.php and example.php in `/plugins/form-data-collector/example` folder.

You can see a list of utilities and hooks [here](https://github.com/taunoha/form-data-collector/wiki/).

**Not compatible with 1.x.x versions :(**

## Installation

1. Go to your admin area and select Plugins -> Add new from the menu.
2. Search for "Form Data Collector".
3. Click install.
4. Click activate.
5. A new menu item called "FDC" will be available in Admin menu.

## Changelog

### 2.1.0
* Introduced `fdc_pre_get_entries` action hook. It works like Wordpress core `pre_get_posts` action.
* `fdc_get_entries()` now accepts meta_query as parameter. It works similarly to [WP_Query](https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters) meta_query parameter.
* `fdc_get_entries()` now accepts date_query as parameter. It works similarly to [WP_Query](https://codex.wordpress.org/Class_Reference/WP_Query#Date_Parameters) date_query parameter.
* `fdc_get_entries()` the parameter `entry_date_after` was replaced with the `date_query` parameter.

### 2.0.1
* Minor bug fixes

### 2.0.0
* Total rewrite. Not compatible with previous versions :(
* Added custom database tables
* Added utilities to insert, get and update data
* Added support for file(s) upload.
* Now `fdc.ajax.post` accepts also javascript object as first parameter (Beta)
* New hooks
* Renamed `restrict_manage_px_fdc` action hook to `fdc_restrict_manage_entries`
* Removed CMB2
* Bootstrap Modal was replaced with Thickbox

### 1.3.1
* Updated CMB2 code

### 1.3.0
* NEW: Now you can store all fields as one meta_key value and still use get_post_meta() to access them. Use the `fdc_store_fields_as_array` filter to enable this feature (Default: false).
* Added action `fdc_overview_details_before_output`
* added action `fdc_overview_details_after_output`
* Updated usage info

### 1.2.0
* Introduced AJAX utility `fdc.ajax.post()` to send POST request to WordPress
* Added filter `fdc_ajax_response_error` to filter AJAX error response
* Added filter `fdc_ajax_response_success` to filter AJAX success response
* Added filter `fdc_enable_email_settings` to enable or disable email settings subpage (Default: true)
* Code clean up

### 1.1.3
* Added 'CMB2_LOADED' constant check

### 1.1.2
* WP_List_Table Class check is now in admin_init hook
* Minor updates

### 1.1.1
* Updated usage info and some text in code.
* Added loading state to Entry modal.

### 1.1
* Added `restrict_manage_px_fdc` action hook. Now you can add restriction filters to the Entries view. Combine this hook with `parse_query` filter to manage the output of Entries list.
* Added date column in the Entries view is now displayed by default.

### 1.0
* Initial release.

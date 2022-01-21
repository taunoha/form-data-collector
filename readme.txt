=== Form Data Collector ===
Contributors: taunoh
Donate link: https://klipper.ee
Tags: form, email, forms, input, ajax, database
Requires at least: 5.5
Tested up to: 5.9
Stable tag: 2.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will help you to collect and store form data.


== Description ==

This plugin is a developer’s toolkit for collecting form data from your WordPress site. It provides the necessary hooks and utilities for you to manage how data is stored and displayed later.

The best way to get started is to look at example-functions.php and example.php in `/plugins/form-data-collector/example` folder.

You can see a list of utilities and hooks [here](https://github.com/taunoha/form-data-collector/wiki/).

**Not compatible with 1.x.x versions :(**

== Installation ==

1. Go to your admin area and select Plugins -> Add new from the menu.
2. Search for "Form Data Collector".
3. Click install.
4. Click activate.
5. A new menu item called "FDC" will be available in Admin menu.

== Changelog ==

= Unreleased =
* Improved Multisite support.
* Added notification to admin menu if there are new entries. New entries check has 15 minutes delay.


= 2.2.2 =
* Added ´fdc_privacy_policy_content´ filter to add suggested privacy policy text to the policy postbox.
* Fixed a meta_value serializing bug
* Minor bug fixes

= 2.2.1 =
* Minor bug fixes

= 2.2.0 =
* Added an option to force delete an entry and all its data.
* Updated how to validate inserted data before it will be inserted into database. It uses [WP_Error](https://codex.wordpress.org/Class_Reference/WP_Error) class. Take a look at the examples.
* Improved error handling.

= 2.1.0 =
* Introduced `fdc_pre_get_entries` action hook. It works like Wordpress core `pre_get_posts` action.
* `fdc_get_entries()` now accepts meta_query as parameter. It works similarly to [WP_Query](https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters) meta_query parameter.
* `fdc_get_entries()` now accepts date_query as parameter. It works similarly to [WP_Query](https://codex.wordpress.org/Class_Reference/WP_Query#Date_Parameters) date_query parameter.
* `fdc_get_entries()` the parameter `entry_date_after` was replaced with the `date_query` parameter.

= 2.0.1 =
* Minor bug fixes

= 2.0.0 =
* Total rewrite. Not compatible with previous versions :(
* Added custom database tables
* Added utilities to insert, get and update data
* Added support for file(s) upload.
* Now `fdc.ajax.post` accepts also javascript object as first parameter (Beta)
* New hooks
* Renamed `restrict_manage_px_fdc` action hook to `fdc_restrict_manage_entries`
* Removed CMB2
* Bootstrap Modal was replaced with Thickbox

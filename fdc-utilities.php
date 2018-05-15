<?php

/**
 * Source: https://wp-mix.com/php-get-actual-ip-address/
 */
function fdc_get_real_ip()
{
    $ip = '';

    if (isset($_SERVER)) {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        $ip = getenv('REMOTE_ADDR');
        if (getenv('HTTP_X_FORWARDED_FOR')) $ip = getenv('HTTP_X_FORWARDED_FOR');
        elseif (getenv('HTTP_CLIENT_IP')) $ip = getenv('HTTP_CLIENT_IP');
    }
    $ip = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
    return $ip;
}

function fdc_handle_upload_file($file)
{
    if( !function_exists('wp_handle_upload') ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }

    add_filter('upload_dir', '_fdc_upload_dir');
    $movefile = wp_handle_upload($file, array(
        'test_form' => false,
        'action' => 'fdc_handle_upload'
    ));
    remove_filter('upload_dir', '_fdc_upload_dir');

    if( $movefile && ! isset($movefile['error']) ) {
        return $movefile;
    }

    return $movefile;
}

/**
 * Generate FDC upload dir
 *
 * @access private
 *
 */
function _fdc_upload_dir($dir)
{
    $hash = get_option('fdc_upload_folder_hash', false);

    if( false === $hash ) {
        $hash = wp_generate_password(6, false);
        update_option('fdc_upload_folder_hash', $hash);
    }

    $folder_name = ltrim(apply_filters('fdc_upload_folder_name', 'fdc-' . $hash), '/');

    return array_merge($dir, array(
        'path'   => $dir['basedir'] . '/' . rtrim($folder_name, '/'),
        'url'    => $dir['baseurl'] . '/' . rtrim($folder_name, '/'),
        'subdir' => rtrim($folder_name, '/')
    ));
}

/**
 * Source: http://php.net/manual/en/reserved.variables.files.php#109958
 *
 */
function fdc_diverse_array($vector)
{
    $result = array();

    foreach($vector as $key1 => $value1)
    {
        foreach($value1 as $key2 => $value2)  {
            $result[$key2][$key1] = $value2;
        }
    }

    return $result;
}
/*
 * Source: http://php.net/manual/en/function.preg-grep.php#111673
 *
 */
function fdc_preg_grep_keys($pattern, $input, $flags = 0)
{
    return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
}


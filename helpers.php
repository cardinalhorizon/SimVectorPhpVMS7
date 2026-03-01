<?php

if (!function_exists('sv_setting'))
{
    function sv_setting($key, $default = null)
    {
        $settings = app('sv_settings');
        // for the key, break apart the key by periods
        $keys = explode('.', $key);
        // for each key, check if it exists in the settings array
        foreach ($keys as $key) {
            if (isset($settings[$key])) {
                $settings = $settings[$key];
            } else {
                return $default;
            }
        }
        return $settings;
    }
}
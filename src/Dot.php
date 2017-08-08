<?php
namespace AdminAddonUserManager;

/**
 * Class Dot
 *
 * @package SelvinOrtiz\Dot
 *
 * https://github.com/selvinortiz/dot
 */
class Dot
{
    /**
     * Returns whether or not the $key exists within $arr
     *
     * @param array  $arr
     * @param string $key
     *
     * @return bool
     */
    public static function has($arr, $key)
    {
        if (strpos($key, '.') !== false && count(($keys = explode('.', $key)))) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $arr)) {
                    return false;
                }

                $arr = $arr[$key];
            }

            return true;
        }

        return array_key_exists($key, $arr);
    }

    /**
     * Returns he value of $key if found in $arr or $default
     *
     * @param array       $arr
     * @param string      $key
     * @param null|mixed  $default
     *
     * @return mixed
     */
    public static function get($arr, $key, $default = null)
    {
        if (strpos($key, '.') !== false && count(($keys = explode('.', $key)))) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $arr)) {
                    return $default;
                }

                $arr = $arr[$key];
            }

            return $arr;
        }

        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }

    /**
     * Sets the $value identified by $key inside $arr
     *
     * @param array  &$arr
     * @param string $key
     * @param mixed  $value
     */
    public static function set(array &$arr, $key, $value)
    {
        if (strpos($key, '.') !== false && ($keys = explode('.', $key)) && count($keys)) {
            while (count($keys) > 1) {
                $key = array_shift($keys);

                if (!isset($arr[$key]) || !is_array($arr[$key])) {
                    $arr[$key] = [];
                }

                $arr = &$arr[$key];
            }

            $arr[array_shift($keys)] = $value;
        } else {
            $arr[$key] = $value;
        }
    }

    /**
     * Deletes a $key and its value from the $arr
     *
     * @param  array &$arr
     * @param string $key
     */
    public static function delete(array &$arr, $key)
    {
        if (strpos($key, '.') !== false && ($keys = explode('.', $key)) && count($keys)) {
            while (count($keys) > 1) {
                $arr = &$arr[array_shift($keys)];
            }

            unset($arr[array_shift($keys)]);
        } else {
            unset($arr[$key]);
        }
    }
}
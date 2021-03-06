<?php
/**
 * Represents system cache.
 * 
 * @author vvelikodny
 */
interface Cache {

    /**
     * Adds new pair key/value to cache.
 *
     * @param $key
     * @param $value
     * @param int $time
     * @return void
     */
    function add($key, $value, $time = 0);

    /**
     * Gets value by key if key exists, {@code null} - otherwise.
     * 
     * @param $key
     * @return void
     */
    function get($key);

    /**
     * Flush cache data.
     * 
     * @return void
     */
    function flush();

    /**
     * Gets cache usage statistic.
     *
     * @return void
     */
    function getStatistic();
}
?>
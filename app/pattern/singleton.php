<?php

namespace Gianism\Pattern;

/**
 * Singleton Pattern
 *
 * @package Gianism\Pattern
 * @author Takahashi Fumiki
 * @since 2.0
 */
abstract class Singleton extends Base
{

    /**
     * Instance holder
     *
     * @var \Gianism\Singleton
     */
    private static $instances = array();

    /**
     * Constructor
     *
     * @param array $argument
     */
    abstract protected function __construct( array $argument = array() );

    /**
     * Get instance
     *
     * @param array $argument
     * @return static
     */
    final public static function get_instance( array $argument = array() ){
        $class_name = get_called_class();
        if( !isset(self::$instances[$class_name]) ){
            self::$instances[$class_name] = new $class_name($argument);
        }
        return self::$instances[$class_name];
    }

}
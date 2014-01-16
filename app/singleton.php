<?php

namespace Gianism;

/**
 * Singleton Pattern
 *
 * @package Gianism
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
    protected static $instance = null;

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
     * @return \Gianism\Singleton
     */
    final public static function get_instance( array $argument = array() ){
        $class_name = get_called_class();
        if( is_null($class_name::$instance) ){
            $class_name::$instance = new $class_name($argument);
        }
        return $class_name::$instance;
    }

}
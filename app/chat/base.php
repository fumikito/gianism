<?php

namespace Gianism\Chat;

use Gianism\Pattern\Singleton;


/**
 * Chat class base
 *
 * @package Gianism\Chat
 * @author Takahashi Fumiki
 * @property-read string $chat_table
 * @property-read string $thread_table
 * @property-read string $contact_table
 * @property-read \Gianism\Chat\Main $main
 * @property-read \Gianism\Chat\Thread $thread
 * @property-read \Gianism\Chat\Contact $contact
 */
abstract class Base extends Singleton
{


    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        /** @var \wpdb $wpdb */
        global $wpdb;
        switch($name){
            case 'main':
                return Main::get_instance();
                break;
            case 'thread':
                return Thread::get_instance();
                break;
            case 'contact':
                return Contact::get_instance();
                break;
            case 'chat_table':
            case 'thread_table':
            case 'contact_table':
                $table_name = str_replace('_table', '', $name);
                return $wpdb->prefix.'wpg_'.$table_name;
                break;
            default:
                return parent::__get($name);
                break;
        }
    }
}

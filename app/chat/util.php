<?php

namespace Gianism\Chat;

use Gianism\Pattern\Singleton;


/**
 * Chat class base
 *
 * @package Gianism\Chat
 * @author Takahashi Fumiki
 *
 * @property-read \wpdb $db
 * @property-read int $id Current user's ID
 * @property-read string $chat_table
 * @property-read string $thread_table
 * @property-read string $contact_table
 * @property-read \Gianism\Chat\Main $main
 * @property-read \Gianism\Chat\Thread $thread
 * @property-read \Gianism\Chat\Contact $contact
 */
abstract class Util extends Singleton
{


    /**
     * Short hand for wpdb->get_row
     *
     * @param string $query
     * @return \stdClass|null
     */
    protected function get_row($query){
        $args = func_get_args();
        if(count($args) > 1){
            return $this->db->get_row(call_user_func_array(array($this->db, 'prepare'), $args), OBJECT);
        }else{
            return $this->db->get_row($query, OBJECT);
        }
    }

    /**
     * Short hand for wpdb->get_results
     *
     * @param string $query
     * @return array
     */
    protected function get_results($query){
        $args = func_get_args();
        if(count($args) > 1){
            return (array)$this->db->get_results(call_user_func_array(array($this->db, 'prepare'), $args));
        }else{
            return (array)$this->db->get_results($query);
        }
    }

    /**
     * Short hand for wpdb->get_var
     *
     * @param string $query
     * @return null|string
     */
    protected function get_var($query){
        $args = func_get_args();
        if(count($args) > 1){
            return $this->db->get_var(call_user_func_array(array($this->db, 'prepare'), $args));
        }else{
            return $this->db->get_var($query);
        }
    }

    /**
     * Short and for wpdb->query
     *
     * @param string $query
     * @return false|int
     */
    protected function query($query){
        $args = func_get_args();
        if(count($args) > 1){
            return $this->db->query(call_user_func_array(array($this->db, 'prepare'), $args));
        }else{
            return $this->db->query($query);
        }
    }

    /**
     * Return URL
     *
     * @param int $thread_id
     * @return string|void
     */
    public function thread_url($thread_id = 0){
        $base = admin_url('admin.php?page=wpg-message');
        if($thread_id){
            $base .= '&thread_id='.intval($thread_id);
        }
        return $base;
    }

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
            case 'db':
                return $wpdb;
                break;
            case 'id':
                return (int)get_current_user_id();
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

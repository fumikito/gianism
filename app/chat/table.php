<?php

namespace Gianism\Chat;


/**
 * Create table
 *
 * @package Gianism\Chat
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Table extends Util
{

    /**
     * Table version
     *
     * @const string
     */
    const VERSION = '1.0';

    /**
     * Storage engine
     *
     * @var string
     */
    private  $engine = 'InnoDB';

    /**
     * Constructor
     *
     * Do nothing
     *
     * @param array $argument
     */
    public function __construct( array $argument = array()){
    }

    /**
     * Check table version and create if required.
     */
    public static function check_version(){
        $option = get_option('wpg_chat_table', null);
        if( is_null($option) ){
            // Option is null
            add_option('wpg_chat_table', 0, '', 'no');
        }
        if( current_user_can('manage_options') && version_compare(self::VERSION, $option, '>') ){
            // Need table to be updated.
            self::get_instance()->create_table();
        }
    }

    /**
     * Update table
     */
    private function create_table(){
        // Build queries
        $queries = array();
        // Thread table
        $queries[] = <<<EOS
            CREATE TABLE {$this->thread_table} (
                thread_id BIGINT NOT NULL AUTO_INCREMENT,
                title VARCHAR(256) NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                admin INT NOT NULL DEFAULT 0,
                PRIMARY KEY  (thread_id),
                INDEX  (updated)
            ) ENGINE = {$this->engine} DEFAULT CHARSET = UTF8
EOS;
        // Chat table
        $queries[] = <<<EOS
            CREATE TABLE {$this->chat_table}(
                chat_id BIGINT NOT NULL AUTO_INCREMENT,
                thread_id BIGINT NOT NULL,
                user_id BIGINT NOT NULL,
                message LONGTEXT NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                read DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY  (chat_id),
                INDEX chat (thread_id, created),
                FOREIGN KEY  (thread_id) REFERENCES {$this->thread_table} (thread_id) ON DELETE CASCADE
            ) ENGINE = {$this->engine} DEFAULT CHARSET = UTF8
EOS;
        // Contact list
        $queries[] = <<<EOS
            CREATE TABLE {$this->contact_table}(
                thread_id BIGINT NOT NULL,
                user_id BIGINT NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                approved DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                ejected DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY  (thread_id, user_id),
                INDEX contact (thread_id, created),
                INDEX user_id (user_id, approved, ejected),
                FOREIGN KEY  (thread_id) REFERENCES {$this->thread_table} (thread_id) ON DELETE CASCADE
            ) ENGINE = {$this->engine} DEFAULT CHARSET = UTF8
EOS;
        // Fire db query
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach($queries as $query){
            dbDelta($query);
        }
        update_option('wpg_chat_table', self::VERSION);
        $this->add_message('<strong>[Gianism] </strong>'.$this->_('Database was updated.'));
    }
} 
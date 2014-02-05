<?php

namespace Gianism;


use Gianism\Pattern\Singleton;


/**
 * Message manager
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0.0
 *
 */
class Message extends Singleton
{

    /**
     * Cache key name
     *
     * @var string
     */
    private $cache_key = 'gianism_message_';

    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        // Check if current user has message
        add_action('admin_menu', array($this, 'admin_menu'));
        // Regsiter adminbar
        add_action('admin_bar_menu', array($this, 'regisiter_admin_bar'));
        // Regsiter assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        // Load older
        add_action('wp_ajax_gianism_chat_older', array($this, 'ajax_older'));
        // Delete message
        add_action('wp_ajax_gianism_chat_delete', array($this, 'ajax_delete'));
    }

    /**
     * Register admin bars
     *
     * @param \WP_Admin_Bar $adminbar
     */
    public function regisiter_admin_bar( \WP_Admin_Bar &$adminbar ){
        if( is_user_logged_in() && ($message_count = $this->message_count(get_current_user_id())) ){
            $adminbar->add_node(array(
                'id' => 'gianism-chat',
                'title' => '<span class="ab-icon"></span><span class="ab-label">'.$message_count.'</span>',
                'href' => admin_url('admin.php?page=wpg-message'),
                'parent' => 'top-secondary',
            ));
        }
    }

    /**
     * Enqueue assets
     */
    public function enqueue_scripts( $path ){
        wp_enqueue_style('gisniam-message', $this->url.'assets/compass/stylesheets/chat_admin.css', array(), $this->version);
        if( 'toplevel_page_wpg-message' == $path ){
            wp_enqueue_script('gianism-chat-helper', $this->url.'assets/compass/js/chat-helper.js', array('jquery-form', 'jquery-effects-highlight'), $this->version);
        }
    }

    /**
     * Register admin menu
     */
    public function admin_menu(){
        if( is_user_logged_in() && ($message_count = $this->message_count(get_current_user_id())) ){
            add_menu_page($this->_('Message'), $this->_('Message').'<span class="update-plugins"><span class="update-count">'.$message_count.'</span></span>', 'read', 'wpg-message', array($this, 'admin_page'), '', 80);
        }
    }

    /**
     * Load admin template
     */
    public function admin_page(){
        include $this->dir.'templates'.DIRECTORY_SEPARATOR.'message.php';
    }

    /**
     * Get older message
     */
    public function ajax_older(){
        $json = array('success' => false);
        try{
            // Check permission
            if( !is_user_logged_in() || !wp_verify_nonce($this->post('_wp_gianism_nonce'), 'gianism_chat_more_'.get_current_user_id()) ){
                throw new \Exception($this->_('You have no permission.'));
            }
            // Get data
            $oldest = $this->post('oldest');
            $result = $this->get_message_box(get_current_user_id(), $oldest);
            if( $result ){
                $json['success'] = true;
                $json['html'] = array();
                foreach( $result as $chat ){
                    $json['html'][] = $this->render_message($chat);
                    $json['oldest'] = $chat->umeta_id;
                }
            }else{
                $json['success'] = true;
                $json['message'] = $this->_('You have no more messages.');
            }
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Delete message via Ajax
     */
    public function ajax_delete(){
        global $wpdb;
        $json = array('success' => false);
        try{
            // Check permission
            if( !is_user_logged_in() || !wp_verify_nonce($this->post('_wp_gianism_nonce'), 'gianism_chat_more_'.get_current_user_id()) ){
                throw new \Exception($this->_('You have no permission.'));
            }
            // Get data
            $umeta_id = $this->post('umeta_id');
            $query= <<<EOS
                DELETE FROM {$wpdb->usermeta}
                WHERE user_id = %d
                  AND umeta_id = %d
                  AND meta_key = %s
EOS;
            $result = $wpdb->query($wpdb->prepare($query, get_current_user_id(), $umeta_id, $this->message_key_name));
            if($result){
                wp_cache_set( $this->cache_key.get_current_user_id(), $this->raw_message_count(get_current_user_id()) );
                $json['success'] = true;
            }else{
                $json['message'] = $this->_('Failed to delete message. Cheatin\'?');
            }
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Get message
     *
     * @param $user_id
     * @param int $oldest
     * @return array
     */
    public function get_message_box($user_id, $oldest = 0){
        global $wpdb;
        $sub_query = $oldest ? ' AND umeta_id < '.intval($oldest) : '';
        $query = <<<EOS
            SELECT * FROM {$wpdb->usermeta}
            WHERE user_id = %d
              AND meta_key = %s {$sub_query}
            ORDER BY umeta_id DESC
            LIMIT 10
EOS;
        return $wpdb->get_results($wpdb->prepare($query, $user_id, $this->message_key_name));
    }

    /**
     * Render chat list
     *
     * @param \stdClass $message
     * @return string
     */
    private function render_message( \stdClass $message ){
        $vars = unserialize($message->meta_value);
        $time = mysql2date(get_option('date_format').' '.get_option('time_format'), $vars['created']);
        if( !$vars['from'] || !($user = get_userdata($vars['from'])) ){
            $name =  get_bloginfo('name');
            $avatar = get_avatar(get_option('admin_email'));
        }else{
            $name = $user->display_name;
            $avatar = get_avatar($vars['from']);
        }
        $subject = esc_html($vars['subject']);
        $body = nl2br(esc_html($vars['message']));
        $button_text = $this->_('Delete');
        return <<<EOS
            <li class="chat-block me" data-message-id="{$message->umeta_id}" data-chat-owner="{$message->user_id}">
                <div class="profile">
                    {$avatar}
                    <cite>{$name}</cite>
                </div>
                <span class="time">{$time}</span>
                <p class="body">
                    <strong class="title">{$subject}</strong>
                    {$body}
                    <span class="delete"><a class="button" href="#">{$button_text}</a></span>
                </p>
            </li>
EOS;
    }

    /**
     * Returns message count
     *
     * @param int $user_id
     * @return int
     */
    private function message_count($user_id){
        $count = wp_cache_get( $this->cache_key.$user_id );
        if ( false === $count ) {
            wp_cache_set( $this->cache_key.$user_id, $this->raw_message_count($user_id) );
        }
        return $count;
    }

    /**
     * Return raw data of message count
     *
     * @param int $user_id
     * @return int
     */
    private function raw_message_count($user_id){
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = <<<EOS
                SELECT count(umeta_id) FROM {$wpdb->usermeta}
                WHERE user_id = %d
                  AND meta_key = %s
EOS;
        return (int)$wpdb->get_var( $wpdb->prepare($query, $user_id, $this->message_key_name) );
    }

    /**
     * Save message
     *
     * @param int $user_id
     * @param bool $body
     * @param int $from
     * @param string $subject
     * @return bool
     */
    public function send_message($user_id, $body, $from = 0, $subject = ''){
        $now = current_time('mysql');
        if( add_user_meta($user_id, $this->message_key_name, array(
            'subject' => $subject,
            'message' => $body,
            'from' => $from,
            'created' => $now,
        ))){
            wp_cache_set($this->cache_key.$user_id, $this->raw_message_count($user_id));
            return true;
        }else{
            return false;
        }
    }

    /**
     * Getter
     *
     * @param string $name
     * @return mixed|string
     */
    public function __get($name){
        switch($name){
            default:
                return parent::__get($name);
                break;
        }
    }
}
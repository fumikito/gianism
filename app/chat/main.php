<?php

namespace Gianism\Chat;

class Main extends Util
{
    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array())
    {
        add_action('admin_bar_menu', array($this, 'regisiter_admin_bar'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_css'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_css'));
        add_action('admin_init', function(){
            Table::check_version();
        });
        $self = $this;
        add_action('admin_menu', function() use ($self) {
            add_menu_page($this->_('Message'), $this->_('Message'), 'read', 'wpg-message', array($self, 'admin_page'), '', 3);
        });
        add_action('wp_ajax_gianism_chat', array($this, 'post_chat'));
    }

    /**
     * Render chat page
     */
    public function admin_page(){
        require $this->dir.'templates/chat/body.php';
    }

    /**
     * Enqueue assets for chat
     *
     * For admin bar, globally load css.
     *
     */
    public function enqueue_css($hook = ''){
        if( 'toplevel_page_wpg-message' == $hook ){
            wp_enqueue_style('gianism-chat-admin', $this->url.'assets/compass/stylesheets/chat_admin.css', null, $this->version);
            wp_enqueue_script('gianism-chat-helper', $this->url.'assets/compass/js/chat-helper.js', array('jquery-form', 'jquery-effects-highlight'), $this->version);
        }
        wp_enqueue_style('gianism-chat', $this->url.'assets/compass/stylesheets/chat.css', null, $this->version);
    }

    /**
     * Register admin bars
     *
     * @param \WP_Admin_Bar $adminbar
     */
    public function regisiter_admin_bar( \WP_Admin_Bar &$adminbar ){
        $adminbar->add_node(array(
            'id' => 'gianism-chat',
            'title' => '<span class="ab-icon"></span>',
            'href' => admin_url('admin.php'),
            'parent' => 'top-secondary',
        ));
    }

    public function post_chat(){
        $json = array('success' => false);
        try{
            if( !$this->verify_nonce('chat') || !$this->thread->is_allowed($this->id, $this->post('thread_id')) ){
                throw new \Exception($this->_('You have no permission to send message.'));
            }
            $message = $this->post('message');
            if( empty($message) ){
                throw new \Exception($this->_('No message was sent.'));
            }
            $chat = $this->thread->add_chat($message, $this->post('thread_id'));
            if( !$chat ){
                throw new \Exception($this->_('Sorry, but failed to add message. Please try again later.'));
            }
            $json['success'] = true;
            $json['message'] = $this->thread->render_chat($chat, $this->id);
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }
}

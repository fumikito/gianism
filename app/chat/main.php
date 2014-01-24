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
        add_action('wp_ajax_gianism_chat_older', array($this, 'old_chats'));
        add_action('wp_ajax_gianism_chat_newer', array($this, 'new_chats'));
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

    /**
     * Post chat message via Ajax
     *
     */
    public function post_chat(){
        $json = array('success' => false, 'message' => '');
        try{
            $this->is_unauthorized_action();
            $message = $this->post('message');
            if( empty($message) ){
                throw new \Exception($this->_('No message was sent.'));
            }
            $chat = $this->thread->add_chat($message, $this->post('thread_id'));
            if( !$chat ){
                throw new \Exception($this->_('Sorry, but failed to add message. Please try again later.'));
            }
            $newest = $this->post('newest');
            if($newest && ($chats = $this->thread->get_more($chat->thread_id, $newest, false, 0)) ){
                // Get newer chat
                foreach($chats as $c){
                    $json['message'] .= $this->thread->render_chat($c, $this->id);
                }
            }else{
                // No chat
                $json['message'] = $this->thread->render_chat($chat, $this->id);
            }
            $json['success'] = true;
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }


    /**
     * Get older chat
     */
    public function old_chats(){
        $json = array('success' => false);
        try{
            // Check authentication
            $this->is_unauthorized_action();
            $thread_id = $this->post('thread_id');
            $oldest = $this->post('oldest');
            $messages  = $this->thread->get_more($thread_id, $oldest, true);
            if( !empty($messages) ){
                $json['html'] = array();
                foreach($messages as $chat){
                    $json['html'][] = $this->thread->render_chat($chat, $this->id);
                }
            }else{
                $json['message'] = $this->_('No message.');
            }
            $json['success'] = true;
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Retrieve new chat
     */
    public function new_chats(){
        $json = array('success' => false);
        try{
            // Check authentication
            $this->is_unauthorized_action();
            // Get values
            $thread_id = $this->post('thread_id');
            $newest = $this->post('newest');
            if( !is_numeric($newest) ){
                throw new \Exception($this->_('Mmm... Chat ID is wrong.'));
            }
            $chats = $this->thread->get_more($thread_id, $newest, false, 0);
            $json['success'] = true;
            $json['message'] = false;
            if( !empty($chats) ){
                $json['message'] = '';
                foreach($chats as $chat){
                    $json['message'] .= $this->thread->render_chat($chat, $this->id);
                }
            }
        }catch (\Exception $e){
            $json['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($json);
        exit;
    }

    /**
     * Check accessibility and throws error
     *
     * @throws \Exception
     * @return void
     */
    private function is_unauthorized_action(){
        if( !$this->verify_nonce('chat') || !$this->thread->is_allowed($this->id, $this->post('thread_id')) ){
            throw new \Exception($this->_('You have no permission to send message.'));
        }
    }
}

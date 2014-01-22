<?php

namespace Gianism\Chat;

class Main extends Base
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
            add_menu_page($this->_('Message'), $this->_('Message'), 'read', 'wpg-message', array($self, 'admin_page'));
        });
    }

    /**
     * Render chat page
     */
    public function admin_page(){
        require $this->dir.'templates/chat.php';
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
}

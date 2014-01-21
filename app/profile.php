<?php

namespace Gianism;


class Profile extends Pattern\Singleton
{


    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        $option = Option::get_instance();
        // Add Ajax Action
        add_action('wp_ajax_wpg_ajax', array($this, 'ajax_message'));
        // Load assets for profile
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        // Register post type
        add_action('init', array($this, 'register_post_type'));
        // Show connection button on admin screen
        if( $option->is_enabled() ){
            add_action('profile_update', array($this, 'password_update'), 10, 2);
            add_action('show_user_profile', array($this, 'connect_buttons'));
            add_action('show_user_profile', array($this, 'show_direct_message'));
        }
    }

    /**
     * Enqueue Assets on profile page
     *
     * @param string $hook
     */
    public function enqueue_scripts($hook = ''){
        if( $hook == 'profile.php' || (defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE) ){
            $endpoint = admin_url('admin-ajax.php');
            if( !is_ssl() ){
                $endpoint = str_replace('https://', 'http://', $endpoint);
            }
            wp_enqueue_script('wpg-ajax', $this->url."assets/compass/js/message-manager.js", array('jquery-effects-highlight'), $this->version);
            wp_localize_script('wpg-ajax', 'WPG', array(
                'endpoint' => $endpoint,
                'nonce' => wp_create_nonce($this->nonce_action('message')),
                'action' => 'wpg_ajax',
                'deleteConfirm' => $this->_('You really delete this message?'),
                'deleteFailed' => $this->_('You cannot delete this message.'),
                'deleteComplete' => $this->_('No message.')
            ));
        }
    }


    /**
     * Manage Ajax Request
     *
     */
    public function ajax_message(){
        if(wp_verify_nonce($this->request('_wpnonce'), 'wpg_ajax')){
            switch($this->request('type')){
                default:
                    $post = get_post($this->request('post_id'));
                    $json = array('status' => false);
                    if($post && $this->message_post_type == $post->post_type && $post->post_author == get_current_user_id()){
                        wp_delete_post($post->ID);
                        $json['status'] = true;
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($json);
                    die();
                    break;
            }
        }
    }

    /**
     * Register post type for message
     *
     */
    public function register_post_type(){
        register_post_type($this->message_post_type, array(
            'public' => false,
            'label' => $this->_('Messages'),
            'labels' => array(
                'name' => $this->_('Messages'),
                'singular_name' => $this->_('Message')
            )
        ));
    }

    /**
     * Update password and delete meta
     *
     * @param int $user_id
     * @param object $old_user_data
     */
    public function password_update($user_id, $old_user_data){
        $current_user = get_userdata($user_id);
        if( get_user_meta($user_id, '_wpg_unknown_password', true) && $current_user->data->user_pass != $old_user_data->user_pass ){
            // Password changed
            delete_user_meta($user_id, '_wpg_unknown_password');
            $this->add_message($this->_('Your password is now on your own!'));
        }
    }

    /**
     * Show connect buttons
     *
     * @param \WP_User $user
     */
    public function connect_buttons( \WP_User $user ){
        $message = array();
        if( get_user_meta($user->ID, '_wpg_unknown_password', true) ){
            $message[] = $this->_('Your password is automatically generated. If you wisth to disconnect your SNS account, please remember to <a href="#pass1">update password</a> to your own.');
        }
        foreach($this->all_services as $service){
            $instance = $this->get_service_instance($service);
            if( method_exists($instance, 'is_pseudo_mail')){
                if($instance->is_pseudo_mail($user->user_email)){
                    $message[] = $this->_('Your mail address is automatically generated and is pseudo. <a href="#email">Changing it</a> to valid mail address is highly recommended, else you might be unable to log in.');
                    break;
                }
            }
        }
        ?>
        <h3 class="wpg-connect-header"><i class="lsf lsf-link"></i> <?php $this->e('Connection with SNS'); ?></h3>
        <?php if(!empty($message)): ?>
            <p class="wpg-notice"><?php echo implode('<br />', $message) ?></p>
        <?php endif; ?>
        <table class="form-table wpg-connect-table">
            <tbody>
            <?php do_action('gianism_user_profile', $user);?>
            </tbody>
        </table>
        <?php

    }

    /**
     * Show message list
     *
     * @param \WP_User $user
     */
    public function show_direct_message( \WP_User $user ){
        ?>
        <h3 class="wpg-message-header">
            <i class="lsf lsf-balloons"></i> <?php printf($this->_('Message to %s'), $user->display_name); ?>
        </h3>
        <div class="wpg-message">
            <p class="message-container"></p>
            <ol>
            </ol>
        </div><!-- // .wpg-message -->
        <?php
    }

    /**
     * Returns user's message
     *
     * @param \WP_User $user
     * @param int $paged
     * @return array
     */
    protected function get_user_message( \WP_User $user, $paged = 1 ){
        return get_posts(array(
            'post_type' => $this->message_post_type,
            'author' => $user->ID,
            'posts_per_page' => 10,
            'offset' => ( max(1, $paged) - 1) * 10,
            'post_status' => array('publish','private'),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
    }


    protected function render_row(){
        ?>
        <tr>
            <th>
                <?php the_title(); ?><br />
                <small><?php the_time('Y-m-d H:i:s'); ?></small>
            </th>
            <td><?php the_content(); ?></td>
            <td>
                <a href="#<?php the_ID(); ?>" class="button delete"><?php $this->e('Delete'); ?></a>
            </td>
        </tr>
        <?php
    }
}

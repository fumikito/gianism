<?php

namespace Gianism;

/**
 * Profile page controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Profile extends Pattern\Singleton
{


    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        $option = Option::get_instance();
        // Show connection button on admin screen
        if( $option->is_enabled() ){
            add_action('profile_update', array($this, 'profile_updated'), 10, 2);
            add_action('show_user_profile', array($this, 'connect_buttons'));
        }
    }

    /**
     * Update password and delete meta
     *
     * @param int $user_id
     * @param object $old_user_data
     */
    public function profile_updated($user_id, $old_user_data){
        $current_user = get_userdata($user_id);
        // Check if password is on your own.
        if( get_user_meta($user_id, '_wpg_unknown_password', true) && $current_user->user_pass != $old_user_data->user_pass ){
            // Password changed
            delete_user_meta($user_id, '_wpg_unknown_password');
            $this->add_message($this->_('Your password is now on your own!'));
        }
        // Check if email is proper and old one is pseudo
        if( $old_user_data->user_email != $current_user->user_email && $this->has_pseudo_segment($old_user_data->user_email) ){
            if( $this->has_pseudo_segment($current_user->user_email) ){
                // email isn't changed.
                $this->add_message($this->_('You mail address is still pseudo one! Please change it to valid one.'), true);
            }else{
                // O.K.
                $this->add_message($this->_('Your email seems to be valid now.'));
            }
        }
    }

    /**
     * Show connect buttons
     *
     * @param \WP_User $user
     */
    public function connect_buttons( \WP_User $user ){
        $message = array();
        // show password notice
        if( get_user_meta($user->ID, '_wpg_unknown_password', true) ){
            $message[] = $this->_('Your password is automatically generated. Please <strong><a href="#pass1">update password</a> to your own</strong> before disconnecting your account.');
        }
        // Check if mail address is pseudo
        foreach($this->all_services as $service){
            $instance = $this->get_service_instance($service);
            if( method_exists($instance, 'is_pseudo_mail')){
                if($instance->is_pseudo_mail($user->user_email)){
                    $message[] = $this->_('Your mail address is automatically generated and is pseudo. <a href="#email">Changing it</a> to valid mail address is highly recommended, else <strong>you might be unable to log in</strong>.');
                    break;
                }
            }
        }
        ?>
        <h3 class="wpg-connect-header"><i class="lsf lsf-link"></i> <?php $this->e('Connection with Web services'); ?></h3>
        <?php if(!empty($message)): ?>
            <ul class="wpg-notice">
                <?php foreach($message as $msg): ?>
                    <li><?php echo $msg; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <table class="form-table wpg-connect-table">
            <tbody>
            <?php do_action('gianism_user_profile', $user);?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Detect if mail address contains @pseudo
     *
     * @param string $email
     * @return bool
     */
    private function has_pseudo_segment($email){
        return false !== strpos($email, '@pseudo.');
    }
}

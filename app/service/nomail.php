<?php

namespace Gianism\Service;


/**
 * Service which doesn't provide email address
 *
 * @package Gianism\Service
 * @author Takahashi Fumiki
 */
abstract class Nomail extends Common
{

    /**
     * Pseudo domain
     * @var string
     */
    protected $pseudo_domain = '';

    /**
     * Register pseudo mail action
     *
     */
    protected function init_action(){
        add_filter('wp_mail', array($this, 'mail_handler'));
    }

    /**
     * Returns if given mail address is pseudo.
     *
     * @param string $mail
     * @return boolean
     */
    protected function is_pseudo_mail($mail){
        return !empty($this->pseudo_domain) && (false !== strpos($mail, "@".$this->pseudo_domain));
    }

    /**
     * Alternative wp_mail
     *
     * @param int $user_id
     * @param string $subject
     * @param string $message
     * @param array|string $headers
     * @param string $attacehment
     * @return void
     */
    abstract protected function wp_mail($user_id, $subject, $message, $headers, $attacehment);

    /**
     * Override default wp_mai
     *
     * @param array $args
     * @return array
     */
    final public function mail_handler($args){
        /** @var string $subject */
        /** @var string $message */
        /** @var string $headers */
        /** @var string $attchment */
        /** @var string $to */
        extract($args);
        if( $this->is_pseudo_mail($to) && ($user_id = email_exists($to))){
            $this->wp_mail($user_id, $subject, $message, $headers, $attchment);
        }
        return $args;
    }

} 
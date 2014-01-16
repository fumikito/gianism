<?php

namespace Gianism;


class Bootstrap extends Singleton
{


    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        // Start session
        if( !session_id() ){
            session_start();
        }

        $classes = array(
            'YConnect\\YConnectClient',
            'TwitterOAuth',
            'OAuthToken',
            'OAuthSignatureMethod_PLAINTEXT',
            'Google_Client',
            'Facebook',
            'JWT',
        );
        foreach($classes as $class){
            printf('<p> %s : %s </p>', $class, class_exists($class));
        }
        exit;
    }
}
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
        if( !session_id() ){
            session_start();
        }
        $this->_('テスト');
        $this->e('テスト');
        var_dump(
            $this->request('page'),
            $this->url,
            $this->dir
        );
        exit;
    }
}
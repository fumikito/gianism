<?php
/** \file OIDConnectAuthorizationClient.php
 *
 * \brief OpenID Connect Authorization処理クラスです.
 */

/**
 * \class OIDConnectAuthorizationClientクラス
 *
 * \brief Authorizationの機能を実装したサブクラスです.
 */
class OIDConnectAuthorizationClient extends OAuth2AuthorizationClient
{
    /**
     * \private \brief
     */
    private $OIDConnectDisplay = OIDConnectDisplay::PAGE;

    /**
     * \private \brief 
     */
    private $OIDConnectPrompt = OIDConnectPrompt::LOGIN;

    /**
     * \brief OIDConnectAuthorizationClientのインスタンス生成
     */
    public function __construct( $endpoint_url, $client_credential, $response_type=null )
    {
        parent::__construct( $endpoint_url, $client_credential, $response_type );
    }

    /**
     * \brief 認可リクエストメソッド
     *
     * 認可サーバへAuthorozation Codeをリクエストします.
     *
     * @param	$redirect_uri	リダイレクトURI
     * @param	$state	state
     */
    public function requestAuthorizationGrant( $redirect_uri=null, $state=null )
    {
        parent::setParam( "display", $this->OIDConnectDisplay );
        parent::setParam( "prompt", $this->OIDConnectPrompt );

        parent::requestAuthorizationGrant( $redirect_uri, $state );
    }

    /**
     * \brief display設定メソッド
     * @param	$display	display
     */
    public function setDisplay( $display )
    {
        $this->OIDConnectDisplay = $display;
    } 

    /**
     * \brief prompt設定メソッド
     * @param	$prompt	prompt
     */
    public function setPrompt( $prompt )
    {
        $this->OIDConnectPrompt = $prompt;
    }
}


/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

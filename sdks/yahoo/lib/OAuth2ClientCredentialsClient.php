<?php
/** \file OAuth2ClientCredentialsClient.php
 *
 * \brief Authorization Code フローの機能を実装しています.
 */

/**
 * \class OAuth2ClientCredentialsClientクラス
 *
 * \brief Authorization Code フローの機能を実装したクラスです.
 */
class OAuth2ClientCredentialsClient extends OAuth2TokenClient
{
    /**
     * \private \brief scopes
     */
    private $scopes = null;

    /**
     * \private \brief access_token
     */
    private $access_token = null;

    /**
     * \brief OAuth2ClientCredentialsClientのインスタンス生成
     */
    public function __construct( $endpoint_url, $client_credential, $scopes )
    {
        parent::__construct( $endpoint_url, $client_credential );
        $this->scopes = $scopes;
    }

    /**
     * \brief scopes設定メソッド
     * @param	$scopes	scope
     */
    public function setScopes( $scopes )
    {
        $this->scopes = $scopes;
    }

    /**
     * \brief Access Token取得メソッド
     * @return	access_token
     */
    public function getAccessToken()
    {
        if( $this->access_token != null ) {
            return $this->access_token;
        } else {
            return false;
        }
    }

    /**
     * \brief エンドポイントURL設定メソッド
     * @param	$endpoint_url	エンドポイントURL
     */
    protected function _setEndpointUrl( $endpoint_url )
    {
        $this->url = $endpoint_url;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

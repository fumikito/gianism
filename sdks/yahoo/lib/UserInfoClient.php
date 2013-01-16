<?php
/** \file UserInfoClient.php
 *
 * \brief OAuth2 Authorization処理クラスです.
 */

/**
 * \class UserInfoClientクラス
 *
 * \brief Authorizationの機能を実装したクラスです.
 */
class UserInfoClient extends OAuth2ApiClient
{
    /**
     * \private \brief UserInfoエンドポイントURL
     */
    private $url = null;

    /**
     * \private \brief レスポンスタイプ
     */
    private $schema = "openid";

    /**
     * \private \brief idToken オブジェクト
     */
    private $user_info = null;

    /**
     * \brief UserInfoClientのインスタンス生成
     *
     * @param	$endpoint_url	エンドポイントURL
     * @param	$schema	schema
     */
    public function __construct( $endpoint_url, $access_token, $schema=null )
    {
        if( is_string($access_token) )
            $access_token = new OAuth2BearerToken( $access_token, null );

        parent::__construct( $access_token );    

        $this->url  = $endpoint_url;
        $this->access_token = $access_token;

        if( $schema != null ) {
            $this->schema = $schema;
        }
    }

    /**
     * \brief UserInfoエンドポイントリソース取得メソッド
     *
     */
    public function fetchUserInfo()
    {
        parent::setParam( "schema", $this->schema );

        parent::fetchResource( $this->url, "GET" );

        $res_body = parent::getLastResponse();

        $json_response = json_decode( $res_body, true );
        YConnectLogger::debug( "json response(" . get_class() . "::" . __FUNCTION__ . ")", $json_response );
        if( $json_response != null ) {
            if( empty( $json_response["error"] ) ) {
                $this->user_info = (object) $json_response;
            } else {
                $error      = $json_response["error"];
                $error_desc = $json_response["error_description"];
                YConnectLogger::error( $error . "(" . get_class() . "::" . __FUNCTION__ . ")", $error_desc );
                throw new OAuth2ApiException( $error, $error_desc );
            }
        } else {
            YConnectLogger::error( "no_response(" . get_class() . "::" . __FUNCTION__ . ")", "Failed to get the response body" );
            throw new OAuth2ApiException( "no_response", "Failed to get the response body" );
        }
    }

    /**
     * \brief UserInfoオブジェクト取得メソッド
     *
     */
    public function getUserInfo()
    {
        if( $this->user_info != null ) {
            return $this->user_info;
        } else {
            return false;
        }
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

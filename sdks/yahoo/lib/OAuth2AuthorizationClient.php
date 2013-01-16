<?php
/** \file OAuth2AuthorizationClient.php
 *
 * \brief OAuth2 Authorization処理クラスです.
 */

/**
 * \class OAuth2AuthorizationClientクラス
 *
 * \brief Authorizationの機能を実装したクラスです.
 */
class OAuth2AuthorizationClient
{
    /**
     * \private \brief 認可サーバエンドポイントURL
     */
    private $url = null;

    /**
     * \private \brief クレデンシャル
     */
    private $cred = null;

    /**
     * \private \brief レスポンスタイプ
     */
    private $response_type = OAuth2ResponseType::CODE;

    /**
     * \private \brief パラメータ
     */
    private $params = array();

    /**
     * \brief OAuth2AuthorizationClientのインスタンス生成
     *
     * @param	$endpoint_url	エンドポイントURL
     * @param	$client_credential	クライアントクレデンシャル
     * @param	$response_type	response_type
     */
    public function __construct( $endpoint_url, $client_credential, $response_type=null )
    {
        $this->url  = $endpoint_url;
        $this->cred = $client_credential;

        if( $response_type != null ) {
            $this->response_type = $response_type;
        }
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
        self::setParam( "response_type", $this->response_type );
        self::setParam( "client_id", $this->cred->id );

        // RECOMMENEDED
        if( $state != null ) {
            self::setParam( "state", $state );
        }

        // OPTIONAL
        if( $redirect_uri != null ) {
            $encoded_redirect_uri = urlencode( $redirect_uri );
            self::setParam( "redirect_uri", $redirect_uri );
        }

        $query = http_build_query( $this->params );
        $request_uri = $this->url . "?" .  $query;

        YConnectLogger::info( "authorization request(" . get_class() . "::" . __FUNCTION__ . ")", $request_uri );

        header( "Location: " . $request_uri );
        exit();
    }

    /**
     * \brief scope設定メソッド
     * @param	$scope_array	scope名の配列
     */
    public function setScopes( $scope_array )
    {
        $this->params[ "scope" ] = implode( " ", $scope_array );
    }

    /**
     * \brief レスポンスタイプ設定メソッド
     * @param	$response_type	response_type
     */
    public function setResponseType( $response_type )
    {
        $this->response_type = $response_type;
    }

    /**
     * \brief パラメータ設定メソッド
     *
     * パラメータ名が重複している場合、後から追加された値を上書きします.
     *
     * @param	$key	パラメータ名
     * @param	$val	値
     */
    public function setParam( $key, $val )
    {
        $this->params[ $key ] = $val;
    }

    /**
     * \brief 複数パラメータ設定メソッド
     *
     * パラメータ名が重複している場合、後から追加された値を上書きします.
     *
     * @param	$keyval_array	パラメータ名と値の連想配列
     */
    public function setParams( $keyval_array )
    {
        $this->params = array_merge( $this->params, $keyval_array );
    }

    /**
     * \brief 認可サーバエンドポイントURL設定メソッド
     * @param	$endpoint_url	エンドポイントURL
     */
    protected function _setEndpointUrl( $endpoint_url )
    {
        $this->url = $endpoint_url;
    }

}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

<?php
/**
 * \file YConnectClient.php
 *
 * \brief Yahoo! JAPAN Connect クライアントライブラリ
 */

/**
 * \class YConnectClientクラス
 *
 * \brief Yahoo! JAPAN Connect クライアントライブラリ
 */
class YConnectClient
{
    /**
     * \brief Authorization Endpoint
     */
    const AUTHORIZATION_URL = "https://auth.login.yahoo.co.jp/yconnect/v1/authorization";

    /**
     * \brief Token Endpoint
     */
    const TOKEN_URL = "https://auth.login.yahoo.co.jp/yconnect/v1/token";

    /**
     * \brief UserInfo Endpoint
     */
    const USERINFO_URL = "https://userinfo.yahooapis.jp/yconnect/v1/attribute";

    /**
     * \private \brief ClientCredentialインスタンス
     */
    private $clientCred = null;

    /**
     * \private \brief OAuth2AuthorizationClientインスタンス
     */
    private $auth_client = null;

    /**
     * \private \brief OAuth2AuthorizationCodeClientインスタンス
     */
    private $auth_code_client = null;

    /**
     * \private \brief OAuth2RefreshTokenインスタンス
     */
    private $refresh_token_client = null;

    /**
     * \private \brief OAuth2ClientCredentialsClientインスタンス
     */
    private $client_credentials_client = null;

    /**
     * \private \brief Access Token
     */
    private $access_token = null;

    /**
     * \private \brief Refresh Token
     */
    private $refresh_token = null;

    /**
     * \private \brief Access Token Expiration
     */
    private $expiration = null;

    /**
     * \private \brief IdToken
     */
    private $id_token = null;

    /**
     * \private \brief UserInfo
     */
    private $user_info = null;

    /**
     * \brief インスタンス生成
     *
     * @param	$clientCred	クライアントクレデンシャル
     */
    public function __construct( $clientCred )
    {
        $this->clientCred = $clientCred;
    }

    /**
     * \brief デバッグ用出力メソッド
     *
     * @param $display	true:コンソール出力 false:ログファイル出力
     */
    public function enableDebugMode( $display = false )
    {
        if( $display == true ) YConnectLogger::setLogType( YConnectLogger::CONSOLE_TYPE );
        YConnectLogger::setLogLevel( YConnectLogger::DEBUG );
    }

    /**
     * \brief SSL証明書チェック解除メソッド
     *
     */
    public function disableSSLCheck()
    {
        HttpClient::disableSSLCheck();
    }

    /**
     * \brief 認可リクエストメソッド
     *
     * Authorizationエンドポイントにリクエストして同意画面を表示する。
     *
     * @param	$redirect_uri	クライアントリダイレクトURL
     * @param	$state	state(リクエストとコールバック間の検証用ランダム値)
     * @param	$nonce	nonce(リプレイアタック対策のランダム値)
     * @param	$response_type   response_type
     * @param	$display	display(認証画面タイプ)
     * @param	$prompt	prompt(ログイン、同意画面選択)
     */
    public function requestAuth( $redirect_uri, $state, $nonce, $response_type, $scope = null, $display = null, $prompt = null )
    {
        $auth_client = new OAuth2AuthorizationClient(
            self::AUTHORIZATION_URL,
            $this->clientCred,
            $response_type
        );
        $auth_client->setParam( "nonce", $nonce );
        if( $scope != null ) {
            $auth_client->setParam( "scope", implode( " ", $scope ) );
        }
        if( $display != null ) $auth_client->setParam( "display", $display );
        if( $prompt != null ) {
            $auth_client->setParam( "prompt", implode( " ", $prompt ) );
        }
        $auth_client->requestAuthorizationGrant( $redirect_uri, $state );
    }

    /**
     * \brief サポートしているレスポンス確認メソッド
     *
     * @param	$state	state
     * @param	$scope	scope
     * @throws  OAuth2TokenException
     */
    private function _checkResponse( $state, $scope = null )
    {
        if( !isset( $_GET["state"] ) ) return false;

        if( $state != $_GET["state"] )
            throw new OAuth2TokenException( "not_matched_state", "the state did not match" );

        return true;
    }

    /**
     * \brief 認可コード取得メソッド
     *
     * コールバックURLからAuthorizaiton Codeを抽出します。
     * stateを検証して正しければAuthorizaiton Codeの値を、そうでなければfalseを返します。
     *
     * @param	$state	state
     */
    public function getAuthorizationCode( $state )
    {
        if( self::_checkResponse( $state ) ) {

            $error      = array_key_exists( "error", $_GET ) ? $_GET["error"] : null;
            $error_desc = array_key_exists( "error_description", $_GET ) ? $_GET["error_description"] : null;
            if( !empty( $error ) ) {
                throw new OAuth2TokenException( $error, $error_desc );
            }

            if( !isset( $_GET["code"] ) ) return false;
            return $_GET["code"];
        } else {
            return false;
        }
    }

    /**
     * \brief アクセストークンリクエストメソッド
     *
     * Tokenエンドポイントにリクエストします。
     *
     * @param	$redirect_uri	クライアントリダイレクトURL
     * @param	$code code
     * @param	$nonce nonce
     */
    public function requestAccessToken( $redirect_uri, $code )
    {
        $this->auth_code_client = new OAuth2AuthorizationCodeClient(
            self::TOKEN_URL,
            $this->clientCred,
            $code,
            $redirect_uri
        );
        $token_req_params = array(
            "grant_type" => OAuth2GrantType::AUTHORIZATION_CODE,
            "code"       => $code
        );
        $this->auth_code_client->setParams( $token_req_params );
        $this->auth_code_client->fetchToken();
        $this->access_token  = $this->auth_code_client->getAccessToken();
        $this->refresh_token = $this->auth_code_client->getRefreshToken();
        $this->expiration    = $this->access_token->getExpiration();
        $this->id_token      = $this->auth_code_client->getIdToken();
    }

    /**
     * \brief アクセストークン取得メソッド
     *
     * アクセストークンを取得します。
     *
     * @return	access_token
     */
    public function getAccessToken()
    {
        return $this->access_token->toAuthorizationHeader();
    }

    /**
     * \brief リフレッシュトークン取得メソッド
     *
     * リフレッシュトークンを取得します。
     *
     * @return	refresh_token
     */
    public function getRefreshToken()
    {
        return $this->refresh_token->toAutorizationHeader();
    }

    /**
     * \brief アクセストークン有効期限取得メソッド
     *
     * アクセストークンの有効期限を取得します。
     *
     * @return	expiration
     */
    public function getAccessTokenExpiration()
    {
        return $this->expiration;
    }

    /**
     * \brief IDトークン検証メソッド
     *
     * IDトークンの各パラメータの値を検証します。
     *
     * @return boolean
     */
    public function verifyIdToken( $nonce )
    {
        $id_token_util = new IdTokenUtil( $this->id_token, $nonce, $this->clientCred->id );
    
        return $id_token_util->verify();
    }

    /**
     * \brief IDトークン取得メソッド
     *
     * IDトークンオブジェクトを取得します。
     *
     */
    public function getIdToken()
    {
        return $this->id_token;
    }

    /**
     * \brief アクセストークン更新メソッド
     *
     * Tokenエンドポイントにリクエストします。
     * リフレッシュトークンをつかってアクセストークンを更新します。
     *
     * @param	$refresh_token	リフレッシュトークン
     */
    public function refreshAccessToken( $refresh_token )
    {
        $this->refresh_token_client = new OAuth2RefreshTokenClient(
            self::TOKEN_URL,
            $this->clientCred,
            $refresh_token
        );
        $this->refresh_token_client->fetchToken();
        $this->access_token  = $this->refresh_token_client->getAccessToken();
        $this->expiration    = $this->access_token->getExpiration();
    }

    /**
     * \brief UserInfoリクエストメソッド
     *
     * UserInfoエンドポイントにリクエストします。
     *
     * @param	$access_token	アクセストークン
     */
    public function requestUserInfo( $access_token, $schema=null )
    {
        $this->user_info_client = new UserInfoClient( self::USERINFO_URL, $access_token, $schema );
        $this->user_info_client->fetchUserInfo();
        $this->user_info = $this->user_info_client->getUserInfo();
    }

    /**
     * \brief UserInfo取得メソッド
     *
     * ユーザー識別子などをstdClassのインスタンスとして取得します。
     *
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

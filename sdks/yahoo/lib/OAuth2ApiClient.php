<?php
/** \file OAuth2ApiClient.php
 *
 * \brief Web APIアクセスの機能を提供するクラスを定義しています.
 */

/**
 * \class OAuth2ApiClientクラス
 *
 * \brief Web APIアクセスの機能を提供するクラスです.
 *
 * Web APIアクセスに必要な機能を提供しています. 
 */
class OAuth2ApiClient
{
    /**
     * \private \brief Access Token
     */
    private $token;

    /**
     * \private \brief リクエストパラメータ
     */
    private $params = array();

    /**
     * \private \brief レスポンスボディ
     */
    private $res_body = null;

    /**
     * \private \brief レスポンスステータス
     */
    private $res_status = null;

    /**
     * \private \brief レスポンスエラーステータス
     */
    private $res_error = '';

    /**
     * \brief OAuth2AuthorizationClientのインスタンス生成
     */
    public function __construct($access_token)
    {
        $this->_checkTokenType($access_token);
        $this->token = $access_token;
    }

    /**
     * \brief パラメータ設定メソッド
     *
     * パラメータ名が重複している場合、後から追加された値を上書きします.
     *
     * @param	$parameters パラメータ名と値の連想配列
     */
    protected function setParams($parameters = array())
    {
        if ( !is_array($parameters) )
            throw new UnexpectedValueException('array is required');

        foreach ( $parameters as $key => $val )
            $this->setParam($key, $val);
    }

    /**
     * \brief 複数パラメータ設定メソッド
     *
     * パラメータ名が重複している場合、後から追加された値を上書きします.
     *
     * @param	$key	パラメータ名
     * @param   $val    値
     */
    protected function setParam($key, $val)
    {
        if ( !is_numeric($key) && is_string($key) && is_scalar($val) )
            $this->params[$key] = $val;        
    }

    /**
     * \brief APIエンドポイントリソース取得メソッド
     * @param	$url	APIエンドポイント
     * @param	$method	HTTPリクエストメソッド
     * @throws  UnexpectedValueException
     */
    protected function fetchResource($url, $method)
    {
        $httpClient = new HttpClient();
        $httpClient->setHeader(array($this->token->__toString()));

        switch ( $method ) {
        case 'GET':
            $httpClient->requestGet($url, $this->params);
            break;
        case 'POST':
            $httpClient->requestPost($url, $this->params);
            break;
        case 'PUT':
            $httpClient->requestPut($url, $this->params);
            break;
        case 'DELETE':
            $httpClient->requestDelete($url, $this->params);
            break;
        default:
            throw new UnexpectedValueException('unsupported http method');
        }


        $res_error_header = $httpClient->getResponseHeader('WWW-Authenticate');
        $this->_checkAuthorizationError($res_error_header);

        $this->res_body = $httpClient->getResponseBody();
    }

    /**
     * \brief レスポンス取得メソッド
     * @return	レスポンス
     */
    protected function getLastResponse()
    {
        return $this->res_body;
    }

    /**
     * check supported token type
     * Only Bearer Token is supported as of now
     *
     * @param   $token    Access Token
     * @throws  UnexpectedValueException   
     */
    private function _checkTokenType($token)
    {
        if ( ! $token instanceof OAuth2BearerToken )
            throw new UnexpectedValueException('unsupported Access Token format');
    }

    /**
     * check WebAPI Authorication error
     *
     * @param   $header                 WWW-Authenticate header string
     * @throws  OAuth2TokenException    if WWW-Authenticate is not NULL
     * @see     OAuth2TokenException
     */
    private function _checkAuthorizationError($header)
    {
        if ( $header !== NULL )
            throw new OAuth2TokenException( $header );
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

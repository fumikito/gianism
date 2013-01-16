<?php
/** \file OAuth2TokenClient.php
 *
 * \brief OAuth2 Token処理クラスです.
 */

/**
 * \class OAuth2TokenClientクラス
 *
 * \brief Tokenリクエストの機能を実装したクラスです.
 */
class OAuth2TokenClient
{
    /**
     * \private \brief エンドポイントURL
     */
    private $url = null;

    /**
     * \private \brief パラメータ
     */
    private $params = array();

    /**
     * \private \brief レスポンスボディ
     */
    private $res_body = null;

    /**
     * \private \brief クレデンシャルの文字列
     */
    protected $cred = null;

    /**
     * \brief OAuth2TokenClientのインスタンス生成
     */
    public function __construct( $endpoint_url, $client_credential )
    {
        $this->url  = $endpoint_url;
        $this->cred = $client_credential;
    }

    /**
     * \brief Tokenエンドポイントリソース取得メソッド
     */
    public function fetchToken()
    {
        $httpClient = new HttpClient();
        $httpClient->setHeader( array(
            "Expect:", // POST HTTP 100-continue 無効
            "Authorization: Basic " . $this->cred->toAuthorizationHeader()
        ));
        $httpClient->requestPost( $this->url, $this->params );
        $this->res_body = $httpClient->getResponseBody();
    }

    /**
     * \brief レスポンス取得メソッド
     * @return	レスポンス
     */
    public function getResponse()
    {
        if( $this->res_body != null ) {
            return $this->res_body;
        } else {
            return false;
        }
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
     * \brief エンドポイントURL設定メソッド
     * @param	$endpoint_url	エンドポイントURL
     */
    protected function _setEndpointUrl( $endpoint_url )
    {
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

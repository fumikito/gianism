<?php
/** \file ClientCredential.php
 *
 * \brief クレデンシャルを保持するクラスを定義しています.
 */

/**
 * \class ClientCredentialクラス
 *
 * \brief クレデンシャルを保持するクラスです.
 *
 * 認可サーバ、Tokenサーバのリクエストで用いられるクレデンシャルのクラスです.
 */
class ClientCredential
{
    /**
     * \private \brief client_id
     */
    public $id = null;

    /**
     * \private \brief client_secret
     */
    public $secret = null;

    /**
     * \brief ClientCredentialのインスタンス生成
     *
     * @param	$client_id	Client ID
     * @param	$client_secret	Client Secret
     */
    public function __construct( $client_id, $client_secret )
    {
        $this->id = $client_id;
        $this->secret = $client_secret;
    }

    /**
     * \brief toString
     */
    function __toString()
    {
        return "client_id: " . $this->id . ", client_secret: " . $this->secret;
    }

    /**
     * \brief Authorization Header形式クレデンシャル取得メソッド
     */
    public function toAuthorizationHeader()
    {
        return base64_encode( $this->id . ":" . $this->secret );
    }

    /**
     * \brief クエリ形式取得メソッド
     */
    public function toQueryString()
    {
        $query = http_build_query(
            array(
                "client_id"     => $this->id,
                "client_secret" => $this->secret
            )
        );

        return $query;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

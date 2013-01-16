<?php
/** \file OAuth2BearerToken.php
 *
 * \brief Bearer Tokenを保持するクラスを定義しています.
 */

/**
 * \class OAuth2BearerTokenクラス
 *
 * \brief Bearer Tokenを保持するクラスです.
 *
 * APIアクセスで用いられるBearer Tokenのクラスです.
 */
class OAuth2BearerToken
{
    /**
     * \private \brief access_token
     */
    public $token = null;

    /**
     * \private \brief expiration
     */
    public $exp = null;

    /**
     * \brief OAuth2BearerTokenインスタンス生成
     *
     * @param	$access_token	Access Token
     * @param	$expiration	expiration
     */
    public function __construct( $access_token, $expiration  )
    {
        $this->token      = $access_token;
        $this->exp        = $expiration;
    }

    /**
     * \brief toString
     */
    function __toString()
    {
        return "Authorization: Bearer " . $this->token;
    }

    /**
     * \brief Authorization Header形式トークン取得メソッド
     */
    public function toAuthorizationHeader()
    {
        return $this->token;
    }

    /**
     * \brief クエリ形式トークン取得メソッド
     */
    public function toQueryString()
    {
        $query = http_build_query(
            array(
                "access_token" => $this->token
            )
        );
    
        return $query;
    }

    /**
     * \brief Access Token有効期限取得メソッド
     */
    public function getExpiration()
    {
        return $this->exp;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

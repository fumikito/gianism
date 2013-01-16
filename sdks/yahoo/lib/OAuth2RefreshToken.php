<?php
/** \file OAuth2RefreshToken.php
 *
 * \brief Refresh Tokenを保持するクラスを定義しています.
 */

/**
 * \class OAuth2RefreshTokenクラス
 *
 * \brief Refresh Tokenを保持するクラスです.
 *
 * Access Tokenの更新で用いられるRefresh Tokenのクラスです.
 */
class OAuth2RefreshToken
{
    /**
     * \private \brief refresh_token
     */
    public $token = null;

    /**
     * \brief OAuth2RefreshTokenのインスタンス生成
     *
     * @param	$refresh_token	Refresh Token
     */
    public function __construct( $refresh_token )
    {
        $this->token = $refresh_token;
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
    public function toAutorizationHeader()
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
                "refresh_token" => $this->token
            )
        );
    
        return $query;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

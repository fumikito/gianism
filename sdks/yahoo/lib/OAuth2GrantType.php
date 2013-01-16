<?php
/** \file OAuth2GrantType.php
 *
 * \brief grant_typeの列挙型クラスです.
 */

/**
 * \class OAuth2GrantTypeクラス
 *
 * \brief grant_typeの列挙型クラスです.
 */
class OAuth2GrantType
{
    /**
     * \public \brief authorization_code
     */
    const AUTHORIZATION_CODE = "authorization_code";

    /**
     * \public \brief refresh_token
     */
    const REFRESH_TOKEN = "refresh_token";
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

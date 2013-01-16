<?php
/** \file OAuth2ResponseType.php
 *
 * \brief response_typeの列挙型クラスです.
 */

/**
 * \class OAuth2ResponseTypeクラス
 *
 * \brief response_typeの列挙型クラスです.
 */
class OAuth2ResponseType
{
    /**
     * \public \brief code
     */
    const CODE = "code";

    /**
     * \public \brief code and id token
     */
    const CODE_IDTOKEN = "code id_token";
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

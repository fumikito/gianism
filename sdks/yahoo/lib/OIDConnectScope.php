<?php
/** \file OIDConnectScope.php
 *
 * \brief scopeの列挙型クラスです.
 */

/**
 * \class OIDConnectScopeクラス
 *
 * \brief scopeの列挙型クラスです.
 */
class OIDConnectScope
{
    /**
     * \public \brief openid: ユーザ識別子を取得するための定数です
     */
    const OPENID = "openid";

    /**
     * \public \brief profile: 姓名・生年・性別を取得するための定数です
     */
    const PROFILE = "profile";

    /**
     * \public \brief email: メールアドレスと確認済みフラグを取得するための定数です
     */
    const EMAIL = "email";

    /**
     * \public \brief address: ユーザ登録住所情報を取得するための定数です
     */
    const ADDRESS = "address";
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

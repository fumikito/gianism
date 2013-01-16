<?php
/** \file OIDConnectPrompt.php
 *
 * \brief promptの列挙型クラスです.
 */

/**
 * \class OIDConnectPromptクラス
 *
 * \brief promptの列挙型クラスです.
 */
class OIDConnectPrompt
{
    /**
     * \public \brief login: ログイン
     */
    const LOGIN = "login";

    /**
     * \public \brief consent: ユーザの認可
     */
    const CONSENT = "consent";

    /**
     * \public \brief none: 非表示
     */
    const NONE = "none";

    /**
     * \public \brief 空文字列: 未指定
     */
    const DEFAULT_PROMPT = "";
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

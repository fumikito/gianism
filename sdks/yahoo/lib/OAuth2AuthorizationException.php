<?php
/** \file OAuth2AuthorizationException.php
 *
 * \brief 認可サーバ例外処理クラスを定義しています.
 */

/**
 * \class OAuth2AuthorizationExceptionクラス
 *
 * \brief 認可サーバ例外処理クラスです.
 *
 * 認可サーバ例外処理例外処理クラスです.
 */
class OAuth2AuthorizationException extends Exception
{
    /**
     * \brief \public エラー概要
     */
    public $error = null;

    /**
     * \brief \public エラー詳細
     */
    public $error_desc = null;

    /**
     * \brief インスタンス生成
     *
     * @param	$error	エラー概要
     * @param	$error_desc	エラー詳細
     * @param	$code
     */
    // $previous 5.3以降追加
    // public function __construct( $message, $code = 0, Exception $previous = null ) {
    public function __construct( $error, $error_desc = "", $code = 0 )
    {
        parent::__construct( $error, $code );

        $this->error      = $error;
        $this->error_desc = $error_desc;
    }

    /**
     * \brief scopeエラー確認メソッド
     *
     * @return	true or false
     */
    public function invalidScope()
    {
        if( preg_match( "/invalid_scope/", $this->error ) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * \brief レスポンスタイプエラー確認メソッド
     *
     * @return	true or false
     */
    public function unsupportedResponseType()
    {
        if( preg_match( "/unsupported_response_type/", $this->error ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function __toString()
    {
        $str = __CLASS__ . " (" . $this->code . ") : " . $this->message . ", ";
        $str .= "error: " . $this->error . ", error_desc: " .$this->error_desc;
        
        return $str;
    }

}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

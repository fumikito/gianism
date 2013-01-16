<?php
/** \file IdTokenException.php
 *
 * \brief IDトークン例外処理クラスを定義しています.
 */

/**
 * \class IdTokenExceptionクラス
 *
 * \brief IDトークン例外処理クラスです.
 *
 * IDトークン例外処理クラスです.
 */
class IdTokenException extends Exception
{
    /**
     * \brief エラー概要
     */
    public $error = null;

    /**
     * \brief エラー詳細
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

    public function __toString()
    {
        $str = __CLASS__ . " (" . $this->code . ") : " . $this->message . ", ";
        $str .= "error: " . $this->error . ", error_desc: " .$this->error_desc;

        return $str;
    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

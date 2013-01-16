<?php
/** \file YConnectLogger.php
 *
 * \brief YConnectLoggerクラスです.
 */

/**
 * \class YConnectLoggerクラス
 *
 * \brief ログ機能を実装したクラスです.
 */
class YConnectLogger
{
    /**
     * \brief ログレベル定数 debug
     */
    const DEBUG = 1;

    /**
     * \brief ログレベル定数 info
     */
    const INFO = 2;

    /**
     * \brief ログレベル定数 error
     */
    const ERROR = 3;

    /**
     * \brief ログ出力方法定数
     */
    const CONSOLE_TYPE = "console";

    /**
     * \brief ログ出力方法定数
     */
    const LOG_TYPE = "log";

    /**
     * \private \brief ログ出力方法
     */
    private static $log_type = self::LOG_TYPE; 
    /**
     * \private \brief ログレベル
     */
    private static $log_level = self::ERROR;

    /**
     * \private \brief ログ出力先パス
     */
    private static $log_path = null;

    /**
     * \brief ログ出力方法設定メソッド
     *
     * @param	$log_type ログ出力方法定数(CONSOLE_TYPE or LOG_TYPE)
     */
    public static function setLogType( $log_type )
    {
        if( $log_type == self::CONSOLE_TYPE ) {
            self::$log_type = self::CONSOLE_TYPE;
        } else if( $log_type == self::LOG_TYPE ) {
            self::$log_type = self::LOG_TYPE;
        } else {
            self::$log_type = self::LOG_TYPE;
        }
    }

    /**
     * \brief ログレベル設定メソッド
     * 
     * @param	$log_level	ログレベル定数(DEBUG or INFO or ERROR)
     */
    public static function setLogLevel( $log_level )
    {
        if( $log_level == self::DEBUG ) {
            self::$log_level = $log_level;
        } else if( $log_level == self::INFO ) {
            self::$log_level = $log_level;
        } else if( $log_level == self::ERROR ) {
            self::$log_level = $log_level;
        } else {
            self::$log_level = self::INFO;
        }
    }

    /**
     * \brief ログ出力先パス設定メソッド
     *
     * @param	$log_path	ログ出力先パス
     */
    public static function setLogPath( $log_path )
    {
        self::$log_path = $log_path;
    }

    /**
     * \brief デバッグログ出力メソッド
     *
     * @param	$message	ログメッセージ
     * @param	$object	対象オブジェクト
     */
    public static function debug( $message, $object = null )
    {
        if( self::$log_level <= self::DEBUG )
            self::outputLog( "[YConnect] [DEBUG] " . $message, $object );
    }

    /**
     * \brief 情報ログ出力メソッド
     *
     * @param	$message	ログメッセージ
     * @param	$object	対象オブジェクト
     */
    public static function info( $message, $object = null )
    {
        if( self::$log_level <= self::INFO )
            self::outputLog( "[YConnect] [INFO] " . $message, $object );
    }

    /**
     * \brief エラーログ出力メソッド
     *
     * @param	$message ログメッセージ
     * @param	$object	対象オブジェクト
     */
    public static function error( $message, $object = null )
    {
        if( self::$log_level <= self::ERROR )
            self::outputLog( "[YConnect] [ERROR] " . $message, $object );
    }

    /**
     * \brief 共通ログ出力メソッド
     *
     * @param	$message ログメッセージ
     * @param	$object	対象オブジェクト
     */
    private static function outputLog( $message, $object = null )
    {
        if( self::$log_type == self::CONSOLE_TYPE ) {
            echo $message;
            if( $object != null )
                echo print_r( $object, true );
        } else if( self::$log_type == self::LOG_TYPE ) {
            if( self::$log_path == null ) {
                error_log( $message );
                if( $object != null )
                    error_log( print_r( $object, true ) );
            } else {
                error_log( $message, 3, self::$log_path );
                if( $object != null ) 
                    error_log( print_r( $object, true ), 3, self::$log_path ) ;
            }
        } 

    }
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

<?php

// YConnectライブラリ読み込み
require("YConnect.inc");

// アプリケーションID, シークレッvト
$client_id     = "YOUR_APPLICATION_ID";
$client_secret = "YOUR_SECRET";

// 各パラメータ初期化
$redirect_uri = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"];

// リクエストとコールバック間の検証用のランダムな文字列を指定してください
$state = "44Oq44Ki5YWF44Gr5L+644Gv44Gq44KL77yB";
// リプレイアタック対策のランダムな文字列を指定してください
$nonce = "5YOV44Go5aWR57SE44GX44GmSUTljqjjgavjgarjgaPjgabjgog=";

$response_type = OAuth2ResponseType::CODE_IDTOKEN;
$scope = array(
    OIDConnectScope::OPENID,
    OIDConnectScope::PROFILE,
    OIDConnectScope::EMAIL,
    OIDConnectScope::ADDRESS
);
$display = OIDConnectDisplay::DEFAULT_DISPLAY;
$prompt = array(
    OIDConnectPrompt::DEFAULT_PROMPT
);

// クレデンシャルインスタンス生成
$cred = new ClientCredential( $client_id, $client_secret );
// YConnectクライアントインスタンス生成
$client = new YConnectClient( $cred );

// デバッグ用ログ出力
$client->enableDebugMode();

try {

    // Authorization Codeを取得
    $code_result = $client->getAuthorizationCode( $state );

    if( !$code_result ) {

        /*****************************
             Authorization Request    
        *****************************/

        // Authorizationエンドポイントにリクエスト
        $client->requestAuth(
            $redirect_uri,
            $state,
            $nonce,
            $response_type,
            $scope,
            $display,
            $prompt
        );

    } else {

        /****************************
             Access Token Request    
        ****************************/

        // Tokenエンドポイントにリクエスト
        $client->requestAccessToken(
            $redirect_uri,
            $code_result
        );

        echo "<h1>Access Token Request</h1>";
        // アクセストークン, リフレッシュトークン, IDトークンを取得
        echo "ACCESS TOKEN : " . $client->getAccessToken() . "<br/><br/>";
        echo "REFRESH TOKEN: " . $client->getRefreshToken() . "<br/><br/>";
        echo "EXPIRATION   : " . $client->getAccessTokenExpiration() . "<br/><br/>";

        /*****************************
             Verification ID Token
        *****************************/

        // IDトークンを検証
        $client->verifyIdToken( $nonce );
        echo "ID TOKEN: <br/>";
        echo "<pre>" . print_r( $client->getIdToken(), true ) . "</pre>";

        /************************
             UserInfo Request
        ************************/

        // UserInfoエンドポイントにリクエスト
        $client->requestUserInfo( $client->getAccessToken() );
        echo "<h1>UserInfo Request</h1>";
        echo "UserInfo: <br/>";
        // UserInfo情報を取得
        echo "<pre>" . print_r( $client->getUserInfo(), true ) . "</pre>";

    }

} catch ( OAuth2ApiException $ae ) {

    // アクセストークンが有効期限切れであるかチェック
    if( $ae->invalidToken() ) {

        /************************************
             Refresh Access Token Request
        ************************************/

        try {

            // 保存していたリフレッシュトークンを指定してください
            $refresh_token = "STORED_REFRESH_TOKEN";

            // Tokenエンドポイントにリクエストしてアクセストークンを更新
            $client->refreshAccessToken( $refresh_token );
            echo "<h1>Refresh Access Token Request</h1>";
            echo "ACCESS TOKEN : " . $client->getAccessToken() . "<br/><br/>";
            echo "EXPIRATION   : " . $client->getAccessTokenExpiration();

        } catch ( OAuth2TokenException $te ) {

            // リフレッシュトークンが有効期限切れであるかチェック
            if( $te->invalidGrant() ) {
                // はじめのAuthorizationエンドポイントリクエストからやり直してください
                echo "<h1>Refresh Token has Expired</h1>";
            }

            echo "<pre>" . print_r( $te, true ) . "</pre>";

        } catch ( Exception $e ) {
            echo "<pre>" . print_r( $e, true ) . "</pre>";
        }

    } else if( $ae->invalidRequest() ) {
        echo "<h1>Invalid Request</h1>";
        echo "<pre>" . print_r( $ae, true ) . "</pre>";
    } else {
        echo "<h1>Other Error</h1>";
        echo "<pre>" . print_r( $ae, true ) . "</pre>";
    }

} catch ( Exception $e ) {
    echo "<pre>" . print_r( $e, true ) . "</pre>";
}

/* vim:ts=4:sw=4:sts=0:tw=0:ft=php:set et: */

YConnect PHP SDK
バージョン：1.0.1（2012/12/13）

■使用API：YConnect + UserInfo API
■構成環境：PHP 5.2 （5.2.17）以降
 
■サンプル名：YConnect PHP サンプル
■概要：
YConnectのAuthorization Codeフローを実装するためのライブラリーです。
実装に必要なクラスファイルが定義されています。
本SDKを利用するためには他に必要なライブラリーがあります。
実装方法の詳細に関しては以下を参照してください。

「PHPアプリ（Authorization Codeフロー）」
http://developer.yahoo.co.jp/yconnect/server_app/sample/php_explicit.html
 
サンプルご利用の際は、利用規約をご覧ください。
利用規約は、ダウンロードパッケージ内のLICENSE.txtファイルに記載されています。 
ダウンロードしたアーカイブファイルを展開すると下記のようになります。
├─ LICENSE.txt
├─ README.txt
├─ lib
│    ├─ ClientCredential.php
│    ├─ HttpClient.php
│    ├─ IdToken.php
│    ├─ IdTokenException.php
│    ├─ IdTokenUtil.php
│    ├─ OAuth2ApiClient.php
│    ├─ OAuth2ApiException.php
│    ├─ OAuth2AuthorizationClient.php
│    ├─ OAuth2AuthorizationCodeClient.php
│    ├─ OAuth2AuthorizationException.php
│    ├─ OAuth2BearerToken.php
│    ├─ OAuth2ClientCredentialsClient.php
│    ├─ OAuth2GrantType.php
│    ├─ OAuth2RefreshToken.php
│    ├─ OAuth2RefreshTokenClient.php
│    ├─ OAuth2ResponseType.php
│    ├─ OAuth2TokenClient.php
│    ├─ OAuth2TokenException.php
│    ├─ OIDConnectAuthorizationClient.php
│    ├─ OIDConnectDisplay.php
│    ├─ OIDConnectPrompt.php
│    ├─ OIDConnectScope.php
│    ├─ UserInfoClient.php
│    ├─ YConnect.inc
│    ├─ YConnectClient.php
│    └─ YConnectLogger.php
├─ index.html
└─ sample.php
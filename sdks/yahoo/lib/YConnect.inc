<?php

// 例外クラス
require("OAuth2AuthorizationException.php");
require("OAuth2TokenException.php");
require("OAuth2ApiException.php");
require("IdTokenException.php");

// ユーティリティクラス
require("YConnectLogger.php");
require("HttpClient.php");
require("IdTokenUtil.php");

// クレデンシャル・トークンクラス
require("ClientCredential.php");
require("OAuth2BearerToken.php");
require("OAuth2RefreshToken.php");
require("IdToken.php");

// 定数定義クラス
require("OAuth2GrantType.php");
require("OAuth2ResponseType.php");
require("OIDConnectDisplay.php");
require("OIDConnectPrompt.php");
require("OIDConnectScope.php");

// 各エンドポイントクライアント
require("OAuth2TokenClient.php");
require("OAuth2AuthorizationCodeClient.php");
require("OAuth2ClientCredentialsClient.php");
require("OAuth2RefreshTokenClient.php");
require("OAuth2AuthorizationClient.php");
require("OAuth2ApiClient.php");
require("UserInfoClient.php");

// YConnectクライアントクラス
require("YConnectClient.php");

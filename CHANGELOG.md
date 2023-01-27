# Change log of Gianism

Here is a list of old change logs. Please refer [readme.md](https://github.com/fumikito/Gianism/blob/master/readme.md) for newer version.

## Old Change Logs

### 4.4.0

* Support [Simple Membership](https://wordpress.org/plugins/simple-membership/) plugin. You need to turn on **"Enable Auto Create Member Accounts"** and **"Force WP User Syncronization"**.
* Supported multiple `to` in `wp_mail`. If `wp_mail` try to send email to multiple users with array of emails, Gianism filter psedudo email. Thanks [@yutaka12](https://github.com/fumikito/Gianism/pull/105)!

### 4.3.4

* Add filter to customize login button order.
* Add 2 short codes `gianism_login` and `gianism_connection` to display SNS buttons in public pages.
* Add new function `gianism_connection` to display SNS connection buttons for logged in users.

### 4.3.3

* Fix child site redirection on failuer under network site.
* Fix redirect users to My Account if WooCommerce is activated.

### 4.3.2

* Fix fatal error caused if WooCommerce is activated.

### 4.3.1

* Fix informal message "Oops".

### 4.3.0

* Support network activation.

### 4.2.2

* Fix Cookie related bugs in PHP 7.3 and over.


### 4.2.0

* Add profile completion options. Now you can notify users or redirect users to profile page if they have incomplete information(e.g. Wrong email). For more details, see [our blog post](https://gianism.info/2020/09/08/complete-user-profile/).

### 4.1.0

* "Add friend" button for [LINE](https://developers.line.biz/en/docs/line-login/link-a-bot/#getting-the-friendship-status-of-the-user-and-the-line-official-account) if you have an official account.

### 4.0.0

4.0 is major update. Please check the changes.

* **BREAKING CHANGE** Requires PHP 5.6 and higher.
* **BREAKING CHANGE** PHP Session is not required anymore. Now Gianism uses Cookie instead. Therefore, please check Cookie's name below are passed to PHP. On environments under CDN like Amazon Cloudfront, Cookies are filtered with a whitelist.
  * `gianism_session`
  * `gianism_updated`
  * `gianism_error`
  * If possible, allow cookie prefixed `gianism_` for future updates.
* All Cookie headers have now new property `SameSite=Lax`.
* URL prefix for redirect URI is now available. Since previous version, Ginanism's endpoints are at root(e.g. `example.com/facebook`). Now you can add prefix as you like(e.g. `example.com/gianism/facebook`). Sorry for late update, but I was young.
* Facebook API version update is very frequent, so Gianims ensure minimum API version. Gianism 4.0 igonres Facebook API version less than 6.0.
* Remove unused libraries of Google PHP API Client from Gianism, because the library sizes are too huge. This may break your site if you use Google's library in your custom code. To check remaining libraries, refer `bin/composer-fixer.php`.
* Some small fixes.

### 3.3.0

* Add consent screen for LINE login. IF you need email, use this screen as screenshot.
* Remove `link` permission because facebook [deprecated it](https://developers.facebook.com/docs/graph-api/changelog/version3.0).

### 3.2.2

* wp-gianism-ja.mo file is missed. Compilation has been forgotten.

### 3.2.1

* Fix translation error. Thank you, [velthgithub](https://github.com/fumikito/Gianism/pull/77)!
* Allow custom option to save array. Thank you, [noellabo](https://github.com/fumikito/Gianism/pull/75)!

### 3.2.0

* LINE is available.
* Now facebook user can regsiter without email.

### 3.1.0

* Add support for [WP Members](https://wordpress.org/plugins/wp-members/). Now your users will be redirected to original page.

### 3.0.9

* Fixed bug based on [facebook login policy change](https://developers.facebook.com/blog/post/2017/12/18/strict-uri-matching/). See our [blog post](https://gianism.info/2018/03/23/failed-facebook-login-since-2018/) for more detail.

### 3.0.8

* Add instant article functions internally.

### 3.0.7

* Add twitter media upload feature.

### 3.0.6

* Add facebook page api feature because we thought it had been available but actually wasn't.
  For details, see our [blog post](https://gianism.info/2017/09/09/post-as-facebook-page/).

### 3.0.5

* Fix error message on session availability check.

### 3.0.4

* Fix fatal error on utility function call([detail](https://github.com/fumikito/Gianism/pull/70)). Thanks [@atomita](https://github.com/atomita)!
* Now you can get twitter email(extra setting on [twitter application manager](https://apps.twitter.com/) required)!
* WooCommerce supported.

### 3.0.3

* Twitter login now requires PHP 5.5 and over.
* If `redirect_to` query is set, redirect user.
* Change error message.

### 3.0.2

* Minor bugfix on bootstrap file `wp-gianism.php`. PHP < 5.4 failed with syntax error.

### 3.0.1

* Minor bugfix on Facebook API page.

### 3.0.0

This release has **breaking changes**. Please check carefully change logs.

* PHP version should be 5.4 and over.
* Mainly supported SNS are now below.
  * Facebook
  * Google
  * twitter
  * Instagram
* Dropped services(mixi, Yahoo! Japan, github, Amazon) are reborned as addons! Please visit our [addon list](https://gianism.info/add-on/).
* As mentioned above, Gianism now have addon system. You can add new addons.
* Mail fall back is dropped. So, some user might miss your important notification.

**NOTICE:** Please update Google Analytics token if you use it!

### 2.2.7

* Follow facebook Graph API 2.4 change. See detail at [Introducing Graph API v2.4](https://developers.facebook.com/blog/post/2015/07/08/graph-api-v2.4/).

### 2.2.6

* Quit loading Facebook JS SDK.

### 2.2.5
* Fix Typo.
* Now you can specify redirect URL with function `gianism_login()`. very cool!

### 2.2.4

* Add feature. Now you can add Ajax classes to your theme. This is very experimental and requires development experience. 
  You can find it on **Tools** admin screen.
  I will add some nice documentation in the future, so please be patient. If you are interested in it, [ask me](https://twitter.com/takahashifumiki) detail. 

### 2.2.3

* Fix bug with Google Analytics Cron. Change `self::` to `static::`. Sorry for that.

### 2.2.2

* Fix strange layout of TinyMCE. Sorry for that.
* Update **Advanced Usage** doc on admin screen.

### 2.2.1

* Fix syntax highlighter
* Add short hand for Facebook PHP SDK client

### 2.2.0

* Add twitter bot feature. Enable it on setting screen and try it.

### 2.1.1

* Bug fix. Google Analytics' cron fails to merge child theme's folder. Thanks for [Daisuke Takahashi](http://www.extendwings.com).

### 2.1.0

* Stop starting session on every access. Now, session is used only on gianism's original URL, so your cache plugins may work well.
* Add hidden feature for Google Analytics Data API. This is very experimental and hard to explain, so if you are interesteed in, please check **Tools page** on Admin panel.

### 2.0.2

* Fixed typo. Thanks [luminousspice](https://github.com/luminousspice)!

### 2.0.1

* Fix auto-loader. This error has occurred on the server which contains capital letter in document root path. Sorry for that.

### 2.0.0

* Add Amazon, Github
* Requires PHP 5.3 or later. Using name space and auto loader.
* Design is suit for WordPress 3.8's new admin.
* Login buttons are redesigned. Now you can choose large flat buttons or normal buttons.

###  1.3.1  

* Follow [twitter's API 1.1 Retirement](https://dev.twitter.com/blog/api-v1-is-retired).

###  1.3.0 

* Updated Facebook PHP SDK to 3.2.2.
* Add function to get publish permission of Faccebook.
* Now you can controll login buttons display.

###  1.2.5 

* Nothing is changed but svn repo is broken, so changed version :(

###  1.2.4 

* Yahoo! JAPAN is added.
* Fixed bug on twitter connection.

###  1.2.3 

* Make manual on admin screen. （管理画面にマニュアルを追記しました）
* Readme is now in English.（readmeが日本語だとユーザーが混乱するようなので、英語も追加）

###  1.2.2 

* バグフィックス
    * Facebookログインがgianism_redirect_toフィルターを通らない問題を修正。サンキュー、[確認さん](http://profiles.wordpress.org/horike/)!
    * mixiログインを有効化したときにエラーが発生する問題を修正

###  1.2.1 

* バグフィックス。mixiの値によって画面が表示されないときがあるので修正。

###  1.2.0 

* mixiでログインできるようにしました。

###  1.1.7 

* バグフィックス。なんということでしょう、Facebookユーザーでユーザー名を持っていない人が登録できない問題を修正

###  1.1.6 

* バグフィックス。Googleアカウントでログインする際にリダイレクトエラーが発生する問題を解消。

###  1.1.5  

* バグフィックス。特定の環境でFacebookへの接続がタイムアウトする問題を修正
* バグフィクス。 Googleアカウントだけを有効にした場合、ボタンが出力されない問題を修正

###  1.1.4 

* バグフィックス。Facebookボタンにバグがあったので修正
* リダイレクト先を変更できるフックを追加

###  1.1.3 

* ログインボタンを好きな場所で出せる関数を追加
* 各ボタンのマークアップを変更するためのフィルターを追加

###  1.1.2 

* Twitterでつぶやく機能を追加しました。詳しくはgianism/functions.phpをご覧下さい。

###  1.1.1 

* Bugfix. Facebookでログインするときのエラーを修正しました。ごめんなさい。

###  1.1 

* Twitterアカウントのみで登録したユーザーにDMを送信するため、強制的にフォローする仕様に変更しました。

###  1.0 

* 公開

##  Upgrade Notice 

###  1.1 

Twitterでログインしたユーザーに自分をフォローさせるため、管理画面からスクリーン名（@xxx）を追加してください。入力しない場合はフォローされない場合もあるため、DMが送信できないことがあります。

###  1.0 

特になし。

# Change log of Gianism

Here is a list of old change logs. Please refer [readme.md](https://github.com/fumikito/Gianism/blob/master/readme.md) for newer version.

## Old Change Logs

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

=== Gianism ===

Contributors: Takahashi_Fumiki  
Tags: facebook,twitter,google,mixi,yahoo,account,oauth,community  
Requires at least: 3.1  
Tested up to: 3.5.1  
Stable tag: 1.3.1

Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!

== Description ==

This plugin enables allow users to login/register with mager SNS accounts. Below is supported SNSs.

If your site needs user's login action, **signing-up is the most difficult step**. With this plugin, users can sign up without inputting email nor password.

------------

このプラグインを使うと、有名なSNSアカウントで新規登録／ログインできるようになります。現在対応しているのは次の通り。

登録制のWordPressサイトを作っている場合、**ユーザーにアカウントを作ってもらうのが一苦労**。このプラグインを使えば、Webサービスの情報を使ってログインできるようになります。ユーザーは新しいユーザー名とパスワードを覚える必要がありません。

= Supported SNS =

* Facebook
* Twitter
* Google *(requires Gmail account)*
* mixi
* Yahoo! JAPAN

= Acknowledgements  =

* Use [Abraham Williams' twitteroauth](https://github.com/abraham/twitteroauth). Thanks.
* Use [Facebook's official PHP SDK](https://github.com/facebook/facebook-php-sdk). 
* Use [Google API PHP Client](http://code.google.com/p/google-api-php-client/).
* Use [Neuman Vong's JWT](https://github.com/luciferous/jwt).
* Iconsets for banner image is by [Arabiki's スタンプみたいなソーシャルアイコン](http://arabikinet.com/sns/sns10.html). Thanks lots.
 
== Installation ==

Install itself is easy. Auto install from admin panel is recommended. Search with `gianism`.

1. Donwload and unpack plugin file, upload `gianims` folder to `/wp-content/plugins` directory.
2. Activate it from admin panel.

-------------

インストール自体は簡単です。管理画面から`gianism`で検索し、自動インストールをお勧めします。

1. プラグインをダウンロード／解凍したら、`gianism`フォルダーを`/wp-content/plugins/`ディレクトリーにアップロード。
2. 管理画面からプラグインを有効化。

= How to set up =

After plugin's activation, you have to set it up. Every setup has 2 step. One is on SNS, the other is on your site.

For example, if you use Facebook, register new app on Facebook, then input app ID and token on WordPress admin panel. Every SNS requires **registeration of Apps** and **credentials related to it**.

It seems too difficult? Don't panic. Howtos and manuals are on setting page *(Users > External)*.

-------------

プラグインを有効化した後、設定を行う必要があります。それぞれの設定には2つのステップがあります。1つは利用するSNSでの設定、もう一つはあなたのサイト上での設定です。

たとえばFacebookを利用する場合、まずFacebookでアプリを登録し、そのアプリに対して発行されたIDとトークンをWordPressの管理画面から入力する必要があります。すべてのSNSは**アプリの登録**と**それに紐づいた認証情報**を求めています。

なんだか複雑なようですが、登録方法については設定画面*（ユーザー > 外部サービス連携）*にマニュアルおよびリンクをつけているので心配いりません。

== Frequently Asked Questions ==

> Can I use this in English?

Maybe yes.

-----------------

> Found bug. It sucks.
> 
> バグがありました。ふざけんな。

Use [support forum on WordPress.org](http://wordpress.org/support/plugin/gianism) or send pull request to [repository on Github](https://github.com/fumikito/Gianism/).

[WordPress.orgのサポートフォーラム](http://wordpress.org/support/plugin/gianism)を使うか、 [Githubのリポジトリ](https://github.com/fumikito/Gianism/)にプルリクエストを送ってください。


== Screenshots ==

1. Buttons on Login/registeration screen. （ログイン／新規登録画面にボタンが追加されます）
2. Show connection status on profile screen. Registered users can connect account here.（プロフィール編集画面に接続ステータスが表示されます。既存のユーザーはここでアカウントを接続できます）
3. Suit to plugins which customize login screen, e.g. [Theme My Login](http://wordpress.org/extend/plugins/theme-my-login/). （[Theme My Login](http://wordpress.org/extend/plugins/theme-my-login/)のようにログイン画面をカスタマイズするプラグインとも同時に動きます）

== Changelog ==

= 1.3.1 = 

* Follow [twitter's API 1.1 Retirement](https://dev.twitter.com/blog/api-v1-is-retired).

= 1.3.0 =

* Updated Facebook PHP SDK to 3.2.2.
* Add function to get publish permission of Faccebook.
* Now you can controll login buttons display.

= 1.2.5 =

* Nothing is changed but svn repo is broken, so changed version :(

= 1.2.4 =

* Yahoo! JAPAN is added.
* Fixed bug on twitter connection.

= 1.2.3 =

* Make manual on admin screen. （管理画面にマニュアルを追記しました）
* Readme is now in English.（readmeが日本語だとユーザーが混乱するようなので、英語も追加）

= 1.2.2 =

* バグフィックス
    * Facebookログインがgianism_redirect_toフィルターを通らない問題を修正。サンキュー、[確認さん](http://profiles.wordpress.org/horike/)!
    * mixiログインを有効化したときにエラーが発生する問題を修正

= 1.2.1 =

* バグフィックス。mixiの値によって画面が表示されないときがあるので修正。

= 1.2.0 =

* mixiでログインできるようにしました。

= 1.1.7 =

* バグフィックス。なんということでしょう、Facebookユーザーでユーザー名を持っていない人が登録できない問題を修正

= 1.1.6 =

* バグフィックス。Googleアカウントでログインする際にリダイレクトエラーが発生する問題を解消。

= 1.1.5 = 

* バグフィックス。特定の環境でFacebookへの接続がタイムアウトする問題を修正
* バグフィクス。 Googleアカウントだけを有効にした場合、ボタンが出力されない問題を修正

= 1.1.4 =

* バグフィックス。Facebookボタンにバグがあったので修正
* リダイレクト先を変更できるフックを追加

= 1.1.3 =

* ログインボタンを好きな場所で出せる関数を追加
* 各ボタンのマークアップを変更するためのフィルターを追加

= 1.1.2 =

* Twitterでつぶやく機能を追加しました。詳しくはgianism/functions.phpをご覧下さい。

= 1.1.1 =

* Bugfix. Facebookでログインするときのエラーを修正しました。ごめんなさい。

= 1.1 =

* Twitterアカウントのみで登録したユーザーにDMを送信するため、強制的にフォローする仕様に変更しました。

= 1.0 =

* 公開

== Upgrade Notice ==

= 1.1 =

Twitterでログインしたユーザーに自分をフォローさせるため、管理画面からスクリーン名（@xxx）を追加してください。入力しない場合はフォローされない場合もあるため、DMが送信できないことがあります。

= 1.0 =

特になし。

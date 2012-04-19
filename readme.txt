=== Gianism ===
Contributors: Takahashi_Fumiki
Tags: facebook,twitter,google,account,oauth,community
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 1.1.7

Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!

== Description ==

このプラグインを使うと、Facebook, twitter, Googleのアカウントで新規登録およびログインできるようになります。
登録制のWordPressサイトを作っている場合、ユーザーにアカウントを作ってもらうのが一苦労。
このプラグインを使えば、Webサービスの情報を使ってログインできるようになります。ユーザーは新しいユーザー名とパスワードを覚える必要がありません。

== Installation ==

インストールはいつもの手順です。

e.g.

1. `gianism`フォルダーを`/wp-content/plugins/`ディレクトリーにアップロード
2. プラグインを有効化
3. 設定ページへ移動し、必要な情報を入力してください。


== Frequently Asked Questions ==

募集中です。

== Screenshots ==

1. ログイン画面・新規登録画面にボタンが表れます。
2. プロフィール編集画面で接続ステータスを表示します。既存のユーザーはこの画面からアカウントを接続できます。

== Changelog ==

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
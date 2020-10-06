#  Gianism 

Contributors: Takahashi_Fumiki, hametuha  
Tags: facebook,twitter,google,instagram,account,oauth,community,social,sns  
Requires at least: 4.7  
Tested up to: 5.5  
Stable tag: 4.3.1  
Requires PHP: 5.6  
License: GPL 2.0 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!

##  Description 

This plugin enables your users to login/register with major Web service's accounts. Below is supported services.

If your site needs user's login action, **signing-up is the most difficult step**. With this plugin, users can sign up without inputting email nor password.

### Log in Flow Video

https://youtu.be/JXl3EMPmXkY

###  Supported Web services

* [Facebook](https://www.facebook.com)
* [Twitter](https://twitter.com)
* [Google](https://google.com)
* [Instagram](https://instagram.com)
* [LINE](https://line.me) *NEW* since 3.2.0

###  Acknowledgements  

* Use [Abraham Williams' twitteroauth](https://github.com/abraham/twitteroauth).
* Use [Facebook's official PHP SDK](https://github.com/facebook/facebook-php-sdk). 
* Use [Google API PHP Client](http://code.google.com/p/google-api-php-client/).
* Use [Ligature Symbols](http://kudakurage.com/ligature_symbols/) (Web font).

Gianism awes a lot to these open source projects. Thanks lots!

##  Installation 

Install itself is easy. Auto install from admin panel is recommended. Search with `gianism`.

1. Donwload and unpack plugin file, upload `gianims` folder to `/wp-content/plugins` directory.
2. Activate it from admin panel.

### Use latest source on github

You can get this plugin from [github](https://github.com/fumikito/Gianism/). Clone it and run `composer install && npm install && npm start`. About composer and npm, google it.

###  How to set up 

After plugin's activation, you have to set it up. Every setup has 2 step. One is on SNS, the other is on your site.

For example, if you use Facebook, register new app on Facebook, then input app ID and token on WordPress admin panel. Every SNS requires **registeration of Apps** and **credentials related to it**.

For more details, please refer to setting screen's instruction or our site [gianism.info](https://gianism.info/).

##  Frequently Asked Questions 

> Can I use this in English?

Maybe yes. Translations are welcomed.

> Found bug. It sucks.

Sorry for that. Please refer to our support site [gianism.info](http://wordpress.org/support/plugin/gianism) or send pull request to [repository on Github](https://github.com/fumikito/Gianism/).

##  Screenshots 

1. Buttons on Login/registration screen.
2. Show connection status on profile screen. Registered users can connect account here.
3. Suit to plugins which customize login screen, e.g. [Theme My Login](http://wordpress.org/extend/plugins/theme-my-login/).

##  Changelog 

Here is a list of change logs.

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

----

Please refer [changelog.md](https://github.com/fumikito/Gianism/blob/master/changelog.md) for older change logs, 
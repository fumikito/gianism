#  Gianism 

Contributors: Takahashi_Fumiki, hametuha  
Tags: facebook,twitter,google,instagram,account,oauth,community,social,sns  
Requires at least: 4.6  
Tested up to: 4.9.5  
Stable tag: 3.3.0  
Requires PHP: 5.4  
License: GPL 2.0 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!

##  Description 

This plugin enables your users to login/register with major Web service's accounts. Below is supported services.

If your site needs user's login action, **signing-up is the most difficult step**. With this plugin, users can sign up without inputting email nor password.

**NOTICE: Requires PHP5.4 and over!**

### Log in Flow Video

https://youtu.be/JXl3EMPmXkY

###  Supported Web services

* [Facebook](https://www.facebook.com)
* [Twitter](https://twitter.com) *Requires PHP >=5.5*
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

Please refer [changelog.md](https://github.com/fumikito/Gianism/blob/master/changelog.md) for older change logs, 
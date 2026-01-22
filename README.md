# Gianism 

Contributors: Takahashi_Fumiki, hametuha  
Tags: facebook,twitter,google,social,sns  
Tested up to: 6.9  
Stable Tag: nightly  
License:  GPL2 or Later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect user accounts with significant web services like Facebook, Twitter, etc. Stand on the shoulders of giants!

##  Description 

This plugin enables users to log in/register with major Web service accounts. Below are supported services.

If your site needs a user's login action, **signing up is the most difficult step**. With this plugin, users can sign up without inputting their email or password.

### Log in Flow Video

https://youtu.be/JXl3EMPmXkY

###  Supported Web services

* [Facebook](https://www.facebook.com)
* [X(formerly known as twitter)](https://x.com)
* [Google](https://google.com)
* [LINE](https://line.me) *NEW* since 3.2.0

###  Acknowledgements  

* Use [Abraham Williams' twitteroauth](https://github.com/abraham/twitteroauth).
* Use [Facebook's official PHP SDK](https://github.com/facebook/facebook-php-sdk). 
* Use [Google API PHP Client](http://code.google.com/p/google-api-php-client/).
* Use [Ligature Symbols](http://kudakurage.com/ligature_symbols/) (Web font).

Gianism awes a lot to these open source projects. Thanks, lots!

##  Installation 

Install itself is easy. Auto installation from the admin panel is recommended. Search with `gianism`.

1. Download and unpack the plugin file, and upload `gianims` folder to `/wp-content/plugins` directory.
2. Activate it from admin panel.

### Use latest source on GitHub

You can get this plugin from [github](https://github.com/fumikito/Gianism/). Clone it and run `composer install && npm install && npm start`. About composer and npm, google it.

###  How to set up 

After the plugin's activation, you have to set it up. Every setup has 2 steps. One is on SNS, the other is on your site.

For example, if you use Facebook, register a new app on Facebook, then input the app ID and token on the WordPress admin panel. Every SNS requires **registration of Apps** and **credentials related to it**.

For more details, please refer to the setting screen's instructions or our site [gianism.info](https://gianism.info/).

##  Frequently Asked Questions 

### Can I use this in English?

Maybe yes. Translations are welcomed.

### Found bug. It sucks.

Sorry for that. Please refer to our support site [gianism.info](http://wordpress.org/support/plugin/gianism) or send a pull request to [repository on Github](https://github.com/fumikito/Gianism/).

##  Screenshots 

1. Buttons on Login/registration screen.
2. Show connection status on the profile screen. Registered users can connect accounts here.
3. Suit to plugins that customize login screen, e.g., [Theme My Login](http://wordpress.org/extend/plugins/theme-my-login/).

##  Changelog 

Here is a list of change logs.

### 5.4.0

* Add workspace limited feature for Google. You can limit the user registration only from your Google Workspace.
* Remove login button styles to follow brands' guideline.
* Add new block **SNS Login Block**

### 5.3.0

* Bump minimum PHP requirements to PHP7.4
* Fix warning on PHP 8.1
* Update certs for archived Facebook PHP SDK 

### 5.2.2

* Fix JS bug.
* Add SNS icon on user list screen in favor of users' connections.

### 5.2.0

* Fix XSS vulnerability.

### 5.1.0

* Supporting twitter API v2.
* Drop some functions like "Follow me", "Sending DM", "Get user's timeline", and so on. The free plan only can post tweet.

### 5.0.2

* Fix the build script to deploy properly.

### 5.0.1

* Bugfix for LINE login. Thanks [Makoto Nakao](https://free-leaf.org/)!

### 5.0.0

* Drop support for Instagram because META denies using Instagram API as login credentials.
* Requires PHP 7.2 and over. Partially supports PHP 8.0.

----

Please refer [changelog.md](https://github.com/fumikito/Gianism/blob/master/changelog.md) for older change logs, 

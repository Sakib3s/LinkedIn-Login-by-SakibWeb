=== LinkedIn Login by SakibWeb ===
Contributors: sakibhasan
Tags: linkedin, social login, login, oauth, openid connect
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight LinkedIn OpenID Connect login for WordPress, WooCommerce and Tutor LMS.

== Description ==

Let visitors log in or register with their LinkedIn account using LinkedIn's "Sign In with LinkedIn using OpenID Connect".

Features:

* LinkedIn button on the WordPress login and registration forms
* Works with WooCommerce and Tutor LMS login/registration forms
* `[linkedin_login]` shortcode to place the button anywhere
* Links to an existing account only when LinkedIn reports the email as verified
* Respects the "Anyone can register" setting when creating new users
* Uses a one-time `state` value to protect the OAuth flow
* No tracking, no ads, no external assets

= Third-party service =

This plugin connects to LinkedIn to authenticate users. When a visitor clicks the LinkedIn button:

* The visitor is redirected to `https://www.linkedin.com/oauth/v2/authorization`.
* After approval, the plugin's server sends the authorization code, your Client ID and Client Secret to `https://www.linkedin.com/oauth/v2/accessToken`.
* The plugin then requests the visitor's name, email address, profile picture URL and LinkedIn ID from `https://api.linkedin.com/v2/userinfo`.

This data is used only to log the visitor in or create their WordPress account. The service is provided by LinkedIn Corporation: [Terms of Service](https://www.linkedin.com/legal/user-agreement), [Privacy Policy](https://www.linkedin.com/legal/privacy-policy).

LinkedIn is a trademark of LinkedIn Corporation. This plugin is not affiliated with or endorsed by LinkedIn.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install it from the Plugins screen.
2. Activate the plugin.
3. Create an app at the LinkedIn Developer Portal and add the product "Sign In with LinkedIn using OpenID Connect".
4. Go to Settings > LinkedIn Login, copy the Redirect URL into your LinkedIn app, and paste your Client ID and Client Secret.
5. Use the `[linkedin_login]` shortcode or the built-in login form button.

== Frequently Asked Questions ==

= Which scopes are used? =

`openid profile email`.

= Can new users be created? =

Yes, if "Anyone can register" is enabled in Settings > General. Developers can override this with the `sakibll_allow_registration` filter.

= How do I change the redirect after login? =

Use the `sakibll_login_redirect` filter.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

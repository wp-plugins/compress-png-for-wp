=== Compress PNG for WP ===
Contributors: geckodesigns
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3CHBFNXBXD3DW
Tags: media, images, image, tinypng, upload, png, resize, shrink
Requires at least: 3.0.1
Tested up to: 3.8
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Compress PNG files using the TinyPNG API.

== Description ==
Compress PNG for WP allows users to shrink PNG files using the TinyPNG API. Files can be automatically resized when uploaded as well as manually resized in the Media Library.

**How to use Compress PNG for WP**

1. Visit 'Settings > Media' from the admin dashboard.
1. Insert your TinyPNG API key and save changes. If you do not yet have a key, get one from [TinyPNG]( https://tinypng.com/developers).
1. Start uploading PNG files and they will be automatically resized (if you have chosen to allow auto shrinking on upload in the 'Settings > Media' page).
1. Visit 'Media > Library' to see information on your resized files or to manually resize existing PNG files.

== Installation ==

**From your Wordpress Dashboard**

1. Visit 'Plugins > Add New'
1. Search for ‘Compress PNG for WP.
1. Activate Compress PNG for WP from your Plugins page. 

**From Wordpress.org**

1. Download Compress PNG for WP.
1. Upload the gecko-tiny-png folder to your '/wp-content/plugins/' directory.
1. Activate Compress PNG for WP from your Plugins page. 

== Frequently Asked Questions ==

= After uploading a PNG file or manually shrinking, I get an error saying Unauthorized: Credentials are invalid. What is wrong?  =

Make sure to enter in your API key obtained from [TinyPNG]( https://tinypng.com/developers) into the correct text box in the 'Settings > Media' page. If you do not remember your key, you can get it by entering in your email in the 'Already Subscribed' box on the [TinyPNG Developers]( https://tinypng.com/developers) page and following the link in the email sent to you.

= Why do I get an error message stating that I have 'Too Many Requests'?  =

Each plan with TinyPNG has a limit of monthly requests. For example the free plan only allows 500 requests per month. If you would like to see how many requests you have made or even upgrade your plan, enter in your email in the 'Already Subscribed' box on the [TinyPNG Developers]( https://tinypng.com/developers) page. Follow the link in the email that you receive to see your API subscription page.

= I get an error message sometimes stating; the php curl extension is not enabled. Compress PNG for WP will not be functional without the use of curl, what is wrong? =

Compress PNG for WP uses the cURL php extension which most servers should already have installed. If you are receiving this message, check with your hosting provider to see if cURL can be enabled. [Read More Here]( http://us1.php.net/manual/en/book.curl.php).


== Screenshots ==

1. Media Library with Compress PNG for WP information column.
2. Media Settings page with Compress PNG for WP settings.


== Changelog ==

= 1.0.2 =
*bug fixes

= 1.0.1 =
*Fix where curl extension is not enabled, now displays error message and allows file upload.

= 1.0 =
*Initial release

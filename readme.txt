=== Compress PNG for WP ===
Contributors: geckodesigns
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3CHBFNXBXD3DW
Tags: media, images, image, tinypng, tinyjpeg, upload, png, jpeg, resize, shrink
Requires at least: 3.0.1
Tested up to: 4.1
Stable tag: 1.3.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Compress PNG files using the TinyPNG API.

== Description ==
Compress PNG for WP allows users to shrink JPEG/PNG files using the TinyPNG API. Files can be automatically resized when uploaded as well as manually resized in the Media Library.

**v1.3 Added Support for JPEG Compression via TinyJPEG, Works the same way as PNG files.**

**How to use Compress PNG for WP**

1. Visit 'Settings > Media' from the admin dashboard.
1. Insert your TinyPNG API key and save changes. If you do not yet have a key, get one from [TinyPNG]( https://tinypng.com/developers). You can also select to auto compress on upload as well as which additional image sizes will be compressed from this page.
1. Start uploading JPEG/PNG files and they will be automatically resized (if you have chosen to allow auto shrinking on upload in the 'Settings > Media' page).
1. Visit 'Media > Library' to see information on your resized files or to manually resize existing JPEG/PNG files.

For more information view our [Compress PNG for WP page](https://www.geckodesigns.com/services/website-design/website-plugins/compress-png-plugin-wordpress/).

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

= Broken Images after Uploading? =

Yes a recent API update from TinyPNG was causing images sent for compression to be overwritten with 0 size.  This has been since fixed in v1.3 but any previously broken images will have to re-uploaded.

= After uploading a JPEG/PNG file or manually shrinking, I get an error saying Unauthorized: Credentials are invalid. What is wrong?  =

Make sure to enter in your API key obtained from [TinyPNG]( https://tinypng.com/developers) into the correct text box in the 'Settings > Media' page. If you do not remember your key, you can get it by entering in your email in the 'Already Subscribed' box on the [TinyPNG Developers]( https://tinypng.com/developers) page and following the link in the email sent to you.

= Why do I get an error message stating that I have 'Too Many Requests'?  =

Each plan with TinyPNG has a limit of monthly requests. For example the free plan only allows 500 requests per month. If you would like to see how many requests you have made or even upgrade your plan, enter in your email in the 'Already Subscribed' box on the [TinyPNG Developers]( https://tinypng.com/developers) page. Follow the link in the email that you receive to see your API subscription page.

= I get an error message sometimes stating; the php curl extension is not enabled. Compress PNG for WP will not be functional without the use of curl, what is wrong? =

Compress PNG for WP uses the cURL php extension which most servers should already have installed. If you are receiving this message, check with your hosting provider to see if cURL can be enabled. [Read More Here]( http://us1.php.net/manual/en/book.curl.php).

= What additional image sizes (i.e. thumbnail, medium, large..) are compressed? =

As of version 1.2 users can select exactly which additional images that are compressed via the 'Settings > Media' page. In version 1.1 only the original file and the large size were compressed. Prior to version 1.1 all images were compressed. However, we found that compressing all images was not efficient and many users were running out of their monthly TinyPNG credits. Since version 1.2 users get the best of both worlds, just remember each additional file size will reduce your TinyPNG monthly credits.

= How can I compress the additional image sizes that WordPress creates? =

You can select which additional image size to compress via the 'Settings > Media' page. Just select the image sizes and compress your image again in the Library page.

*Note: When compressing an image that was originally compressed in a version prior to 1.2, initially any previously compressed images will be compressed again. However, subsequent compressions will only compress images that have not yet been compressed*.

== Screenshots ==

1. Media Library with Compress PNG for WP information column.
2. Media Settings page with Compress PNG for WP settings.


== Changelog ==
= 1.3.5 =
* Localhost development curl ssl fix, was returning 0 byte image when ran in localhost enviorment only.
* Added filesize check for fetched image, returns error message now instead of overwriting image with 0 bytes.

= 1.3.4 =
* Bulk compress JPEG bug fix

= 1.3.3 =
* Changed ratio display value to percentage

= 1.3.2 =
* Fixed unit of measurement for before and after compression file size, now uses kB instead of KiB, to match tinyPNG's unit of measurement

= 1.3.1 =
* Updated readme

= 1.3 =
* Fixed corrupted image bug from recent TinyPNG api update
* Added support for compressing JPEG images via TinyJPEG

= 1.2 =
* Users can select which additional image sizes to compress.
* Compressing an image multiple times will only compress images that are not already compressed, saving TinyPNG calls.

= 1.1 =
* Added bulk compression of PNG files from Media Library.
* Only compress large and original sizes to reduce TinyPNG calls.

= 1.0.2 =
* bug fixes

= 1.0.1 =
* Fix where curl extension is not enabled, now displays error message and allows file upload.

= 1.0 =
* Initial release

== Upgrade Notice ==
= 1.3 =
Allows users to compress JPEG images as well as PNG images.  This is provided via TinyJPEG(TinyPNG's new project) api. JPEG and PNG compression can now be done at the same time and via the same interface (automatic upload, bulk/single compression).

= 1.2 =
Allows users to choose which additional images (thumbnail, medium, large) to compress and only compresses images if not already compressed.

= 1.1 =
Allows bulk compression option in Media -> Library page.

= 1.0.2 =
This version fixes a various bugs.  Upgrade immediately.



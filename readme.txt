=== Tailored Tools ===
Contributors:		tailoredweb, ajferg
Tags:				
Requires at least:	3.0
Tested up to:		4.5.3
Stable tag:			1.9.1

Contains some helper classes to help you build custom forms.

== Description ==

This plugin contains helper classes used to build custom forms.  It's built by [Tailored Web Services](http://www.tailored.com.au "Tailored Web Services") for use on our sites, but anyone is welcome to use it.

This plugin comes with a basic contact form. You can write additional plugins to extend & create more forms. If you are not comfortable writing PHP code, then this plugin is probably not right for you.

It also contains some other shortcode helpers for Google Maps, jQuery UI Tabs, and Page Content.

== Installation ==

1. Install plugin using WordPress Plugin Browser
1. Activate
1. Create & install your custom plugins to extend the form functionality

== Frequently Asked Questions ==

= How do I use this plugin? =

Just activate it!  The plugin comes with a single pre-built contact form.  You can insert the contact form using shortcode: [ContactForm]

= Can you help me create new forms? =

No. This plugin is available as-is.

= So how do I learn how to use it? =

The plugin contains two forms: a contact form, and a sample form.  Have a look at the source code to see how to write your own forms.  If you're not comfortable writing PHP code, this plugin is probably not the best choice for you.

== Shortcodes ==

This plugin also includes some shortcodes that we tend to use a lot.

= [tabs] =

This will apply formatting and javascript to implement [jQuery UI Tabs](http://jqueryui.com/demos/tabs/).  To use, simply wrap all of your tabbed content in [tabs] ... [/tabs] shortcodes.  Each H2 element will be a new tab.  Some basic CSS is included, and you can write your own in your theme file to customise the look.

= [pagecontent id="1"] =

Sometimes you need to include the same bit of content in many places on your site.  To save time, this shortcode will let you include the content from one page in many places.  Just use the shortcode, and provide the ID of the page you want to include.  Eg, [pagecontent id="3"] will insert all content from the page with ID = 3.  You can use [pagecontent id="3" include_title="no"] if you want to include the text only, and not the page title.

= [googlemap address="123 somewhere street, Kansas"] =

To embed a Google Map iframe, use this shortcode.  Google will geocode your address to determine where the pin goes.  You can also specify width, height, and zoom.  You can also provide 'class' to set a CSS class on the iframe element.  This will embed both the iFrame and a static image.  Use CSS to determine which one is shown.  Use CSS media queries for responsive behavior here.


== Upgrade Notice ==

= 1.9.2 =
* Now using more modern dev tooling
* Refactor code without changing function

= 1.9.1 = 
Version bump


= 1.9.0 = 
Add gitlab updater script to update from my private repo.

= 1.8.6 =
This update includes a number of fixes and enhancements.  Check your form settings, and check over your site to ensure everything still works as expected.

= 1.8.0 =
This is a major upgrade, featuring improved anti-spam options and style changes.  You should check the appearance of your forms after this upgrade.  Some themes will require manual tweaking.


== Changelog ==

= 1.9.1 = 
Fix a namespace conflict

= 1.9.0 =
* Moved to git version control
* Update js.php files with finding wp-load for non-standard directories
* Some general fixups
* add a filter `tailored_tools_disable_contact_form` (return true to disable the default contact form, such as if you're using Gravity Forms instead)

= 1.8.8 = 
* Better compatibility with Gravity Forms date fields

= 1.8.7 = 
* Fix the GoogleMaps embedder, so that it will work on HTTPS addresses without security warnings.

= 1.8.6 =
* Change author from Tailored Web Services to Tailored Media
* Update some JS libraries to latest version
* Move some of the init from `plugins_loaded` to `after_setup_theme` so theme functions.php can run before forms
* Add filter to disable the contact form only.  Usage:  `add_filter('tailored_tools_disable_contact_form', '__return_true');`
* Changed class.forms.php wp_enqueue_scripts priority to 9, so it doesn't over-ride the theme css files
* Tweaks to other enqueues to keep things working when contact form disabled.
* Change to CSS Column inserts: now supports two, three, and four columns
* Codebase needs a 2.0 rewrite.


= 1.8.5 =
* Proper enqueue of code for datepicker fields
* Embed JS is back, for easy conversion/tracking code.
* Update some JS libraries to latest version

= 1.8.4 =
* Tweak to select/radio/checkbox outputs, while determine if options are assoc array
* New editor button for content-columns, appears if using Genesis theme (or child)  (one-third, one-half, etc)
* Tweaked code for the Extras MCE button (for shortcodes)
* Change how javascript is enqueued for lighter pageloads.
* Switch from CDN of a few scripts to including them.
* Update a few javascript plugins to latest versions
* Add input type for name (which has two inputs for first and last name)
* Add input types for address and address_long (which use Google Autocomplete)
* Add registered script: jquery-geocomplete 
* Update how logged-data is cleaned for double-quotes. Now stripping slashes and using htmlspecialchars.
* Tested up to WordPress 4.4 beta.  Looks good.

= 1.8.2 =
* Bugfix on form logging (to avoid an error message in certain scenarios when dealing with arrays)
* Tidy up the code to display logged data
* Change array(&$this,'function_name') callbacks to remove &
* Tweaks to default Contact Form options
* CSS tweaks to allow for .block and .wide elements again (left out of 1.8.0 rebuild)
* Fix up some issues with default selections for radio and checkbox inputs

= 1.8.1 =
* Released just to bump the version number.

= 1.8.0 =
* Tested with WordPress 4.2.2
* Update enqueued script versions
* Remove the Chosen script (you can add this to your theme or another plugin if you need it)
* Fix minor bugs
* Contact form now offers Google's "[No CAPTCHA reCAPTCHA](https://www.google.com/recaptcha/)" 
* Change contact form headers (Only REPLY header is set to visitor.  FROM defaults to admin_email setting.  Will hopefully help avoid spam filters.)
* Change Maps embed default width to 1000px
* Include a copy of WP_List_Table class, to avoid issues if core file changes in future. 

= 1.7.7 =
* Changed the function that handles file-uploads for relevant questions.  Previously, file-upload questions within fieldsets would not be included.
* Preserve new-line characters in json_encode function when logging entries

= 1.7.6 =
* Noticed a problem with [tabs] shortcode not including some image elements. Now resolved, plus added a filter for allowed tags (tailored_tools_ui_tabs_allowed_nodes)

= 1.7.5 =
* We were trim()ing a _POST value without checking if it was an array.  Caused issues on at least one client.  Now fixed (checking if array before trim).

= 1.7.4 =
* New shortcode handler for [tabs] had an issue with iframes and other elements. Now corrected.
* Change input type for date,time,datetime elements
* Fix some code that was causing PHP warnings
* Add some CSS for responsive map embed
* Add some CSS for better ui-datepicker default apperance
* Change dateformat from dd-mm-yy to dd/mm/yy (was a problem with validation format)
* Allow a default of 0 on form inputs, and allow a supplied "0" value to pass server-side validation

= 1.7.3 = 
* Update how the [tabs] shortcode is parsed & handled to allow for &lt;H2 class="something"> attributes
* Update the related JS to handle new format, and to allow for <a href="#something"> triggers

= 1.7.2 = 
* Now using json_encode() instead of serialize() when saving arrays to database
* Maintaining backwards compat so that old logged records still readable

= 1.7.1 = 
* Fix a stylesheet problem with jquery-chosen

= 1.7.0 =
* Now including a graph before displaying logs in admin area, to show leads over time.

= 1.6.0 =
* SVN Commit for 1.5.4 didn't include all updates.
* Genesis 2.0+ has an "embed scripts" meta box.  So if box is empty and running 2.0 plus, disable our metabox.
* Change ' to " in shortcodes.php (personal preference)

= 1.5.4 =
* Expanded the allowed "type" for inputs.  Better support for HTML elements like color, date, number, range, tel, email, etc, plus hidden inputs.
* Adjust load-order of files, to allow other plugins to override certain form classes
* Changed the way to list logged form submissions in admin area (old code will still work too)
* When viewing logged form submissions, now broken up by per-page (instead of listings hundreds in one go)

= 1.5.3 =
* Added an admin metabox to make it easier to add AdWords conversion tracking code to pages and posts

= 1.5.2 = 
* Introduce new input types: timepicker, datetimepicker
* Uses JS library: http://trentrichardson.com/examples/timepicker/
* Allow a class of 'nochosen' on select elements to NOT apply the "Chosen" autoloader.

= 1.5.1 =
* Fix a formatting error in readme file that was really annoying

= 1.5.0 =
* Double-checked some Akismet code
* Rewrote style rules for better compatibility with Genesis responsive designs (likely have negative effect on existing sites)
* Improve the Datepicker autoloader, and add an icon
* Added jQuery Chosen and auto-apply to all select boxes (Yes can use MIT license in plugin) https://twitter.com/markjaquith/status/188108457386311681

= 1.4.0 =
* Modify the GoogleMaps shortcode for better responsive behavior.  Now uses Google Static Maps API to grab a JPG before embedding an iFrame.
* Note: your theme will need some additional CSS to take advantage of these features.

= 1.3.9 =
* Add a filter for ttools_form_bad_words_to_check to build a blacklist of words to ban
* If one of those words is in the message, it immediately fails. (Spam check)

= 1.3.8 =
* Change default message to include the current page URI
* Add a filter for ttools_form_filter_email_message

= 1.3.7 =
* Add a shortcode for [googlemap]
* Fix a filter name typo for ttools_form_filter_ignore_fields

= 1.3.6 =
* Fix a PHP depreciation issue

= 1.3.5 =
* Fix issue with ui_tabs - JS and shortcode
* Added some more filters for easier development

= 1.3.4 =
* Fix to apply 'required' class to datepicker elements
* Fix the email header filter

= 1.3.3 =
* Fix the TinyMCE icon
* Allow for non-associative arrays on select and radio elements

= 1.3.1 =
* First official release.


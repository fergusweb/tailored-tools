=== Tailored Tools ===
Contributors:		tailoredweb, ajferg
Tags:				
Requires at least:	3.0
Tested up to:		3.5.1
Stable tag:			1.5.1

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

Just activate it!  The plugin comes with a single pre-set contact form.  You can insert the contact form using shortcode: [ContactForm]

= Can you help me create new forms? =

No. This plugin is available as-is.

= So how do I learn how to use it? =

The plugin contains two forms: a contact form, and a sample form.  Have a look at the source code to see how to write your own forms.  If you're not comfortable with PHP code, this plugin is probably not the best choice for you.

== Shortcodes ==

This plugin also includes some shortcodes that we tend to use a lot.

= [tabs] =

This will apply formatting and javascript to implement [jQuery UI Tabs](http://jqueryui.com/demos/tabs/).  To use, simply wrap all of your tabbed content in [tabs] ... [/tabs] shortcodes.  Each H2 element will be a new tab.  Some basic CSS is included, and you can write your own in your theme file to customise the look.

= [pagecontent id="1"] =

Sometimes you need to include the same bit of content in many places on your site.  To save time, this shortcode will let you include the content from one page in many places.  Just use the shortcode, and provide the ID of the page you want to include.  Eg, [pagecontent id="3"] will insert all content from the page with ID = 3.  You can use [pagecontent id="3" include_title="no"] if you want to include the text only, and not the page title.

= [googlemap address="123 somewhere street, Kansas"] =

To embed a Google Map iframe, use this shortcode.  Google will geocode your address to determine where the pin goes.  You can also specify width, height, and zoom.  You can also provide 'class' to set a CSS class on the iframe element.  This will embed both the iFrame and a static image.  Use CSS to determine which one is shown.  Use CSS media queries for responsive behavior here.


== Changelog ==

= 1.5.1 =
* Fix a formatting error in readme file that was really annoying

= 1.5.0 =
* Double-checked some Akismet code
* Rewrote style rules for better compatibility with Genesis responsive designs (likely have negative effect on existing sites)
* Improvde the Datepicker autloader, and add an icon
* Added jQuery Chosen and auto-apply to all select boxes (Yes can use MIT license in plugin) https://twitter.com/markjaquith/status/188108457386311681

= 1.4.0 =
* Modify the GoogleMaps shortcode for better responsive behavior.  Now uses Google Static Maps API to grab a JPG before embedding an iFrame.
* Note: your theme will need some additional CSS to take advantage of these features.

= 1.3.8 =
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


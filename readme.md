# Tailored Tools

WordPress plugin with some added functionality we frequently desire.

This plugin originally focused on the Contact Form, and includes a class to rapidly develop new forms.  This is less of a focus now, as we use Gravity Forms for everything.

## To Do

- [ ] Extend tabs to work horizontal or vertical
- [ ] Better support for CSS columns (don't rely on the theme either)
- [ ] Admin permissions for form options/log
- [ ] Embed JS option (make sure it doesn't conflict with Genesis) and add to all public post-types.


### TODO: Embed JS on all post-types
Only if post-type is public.
File: embed-js.php
Look for the `check_genesis()` function.  Could just `return false;` to always show the box.
Could also remove that function and any calls to it.

Look at function `add_meta_boxes()`, where it says which post types it adds the box for.  Just Post and Page.  Should also be Product.

```
get_post_types(array('public'=>true));

// One or the other - which is appropriate?
get_post_types(array(
	'public' = >true
	'publicly_queryable' => true 
));
```
https://codex.wordpress.org/Function_Reference/get_post_types


### TODO: Change permissions for admin menu.
Don't want basic contributors to see these options.

File: class.forms.php
Line: 733
Change `edit_posts` to `edit_pages` or something stronger.

## Functions

- [ ] Something
- [ ] Something

## Shortcodes

- [x] `[tabs]`
This will apply formatting and javascript to implement [jQuery UI Tabs](http://jqueryui.com/demos/tabs/).  To use, simply wrap all of your tabbed content in `[tabs] ... [/tabs]` shortcodes.  Each H2 element will be a new tab.  Some basic CSS is included, and you can write your own in your theme file to customise the look.

- [x] `[pagecontent id="1"]`
Sometimes you need to include the same bit of content in many places on your site.  To save time, this shortcode will let you include the content from one page in many places.  Just use the shortcode, and provide the ID of the page you want to include.  Eg, [pagecontent id="3"] will insert all content from the page with ID = 3.  You can use `[pagecontent id="3" include_title="no"]` if you want to include the text only, and not the page title.

- [x] `[googlemap address="123 somewhere street, Kansas"]`
To embed a Google Map iframe, use this shortcode.  Google will geocode your address to determine where the pin goes.  You can also specify width, height, and zoom.  You can also provide 'class' to set a CSS class on the iframe element.  This will embed both the iFrame and a static image.  Use CSS to determine which one is shown.  Use CSS media queries for responsive behavior here.


## Changelog

Do I still want this?

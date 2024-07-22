# Tailored Tools

WordPress plugin with some added functionality we frequently desire.

This plugin originally focused on the Contact Form, and includes a class to rapidly develop new forms.  This is less of a focus now, as we use Gravity Forms for everything.

## Use discouraged
I would discourage the use of this plugin these days. Form builder plugins like Gravity Forms are far better than the form included with this plugin.

I'm keeping this online for the sites still using it, but there are better options these days.

The Gutenberg editor can replace `pagecontent` (using [Synced Patterns](https://wordpress.org/documentation/article/reusable-blocks/)).  Plugins are available for a [Google Maps Block](https://wordpress.org/plugins/embed-maps-block/), and a [Advanced Tabs Block](https://wordpress.org/plugins/advanced-tabs-block/).

## Shortcodes

- `[tabs]`
This will apply formatting and javascript to implement [jQuery UI Tabs](https://jqueryui.com/tabs/).  To use, simply wrap all of your tabbed content in `[tabs] ... [/tabs]` shortcodes.  Each H2 element will be a new tab.  Some basic CSS is included, and you can add your own CSS through your theme or the customiser to tweak the look.

- `[pagecontent id="1"]`
Sometimes you need to include the same bit of content in many places on your site.  To save time, this shortcode will let you include the content from one page in many places.  Just use the shortcode, and provide the ID of the page you want to include.  Eg, [pagecontent id="3"] will insert all content from the page with ID = 3.  You can use `[pagecontent id="3" include_title="no"]` if you want to include the text only, and not the page title.

- `[googlemap address="123 somewhere street, Kansas"]`
To embed a Google Map iframe, use this shortcode.  Google will geocode your address to determine where the pin goes.  You can also specify width, height, and zoom.  You can also provide 'class' to set a CSS class on the iframe element.  This will embed both the iFrame and a static image.  Use CSS to determine which one is shown.  Use CSS media queries for responsive behavior here.



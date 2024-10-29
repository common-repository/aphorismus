=== Aphorismus ===
Contributors: Ivan Ovsyannikov
Donate link: mailto:ovsyannikov.ivan@gmail.com
Tags: post, page, template, widget, aphorism, text block, seo
Requires at least: 2.8
Tested up to: 3.5.1
Stable tag: 1.2.0

The plug-in allows to deduce the random text block (aphorism) on pages, posts or sidebar. Plugin have the widget.

== Description ==

The plug-in allows to deduce the random text block (aphorism) on pages, posts or sidebar. Plugin have the widget.

A new features:

*   Export and import between sites without an intermediate file. Simply under the http-link.

A features:

*   Export to XML-file, import from XML-file;
*   Add, Edit, Delete, Delete all, Find;
*   Possibility to use in a posts and templates;
*   Configuring output;
*   Deduce aphorisms on other sites;
*   Multilanguage;
*   Widget.

Accessible languages:

*   English
*   Russian

(for the present...)

P.S. Many thanks for Stas Chebotarev aka Freest5.

P.S.S. Sorry, I badly know English language... :)

== Installation ==

1. Upload `aphorismus` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. See a 'Help' page for more info...

== Changelog ==

= 1.2.0 =

* Fixed `define('PLUGIN_NAME', 'aphorismus')`. Replaced by `define('PLUGIN_NAME_APHORISMUS', 'aphorismus')`.
* Fixed variable `$upload_path`. Now use the function `wp_upload_dir()` to determine the correct path.
* Fixed encoding table for the initial installation. Now use the `$charset_collate` from the `WP_db`.
* Fixed templates for the widget and the page.
* Fixed `wp_register_sidebar_widget`.

= 1.1.0 =

* Export and import between sites without an intermediate file.
* Changes in import.

= 1.0.0 =
* It is a lot of changes in a code.
* It is completely altered widget.
* Differently storage of options is organised. Now at updating of a plug-in they are not replaced with options by default.
* It is possible to adjust the period of updating of aphorisms (works both in posts, and in widget).
* Changes in export.
* Changes in a call from other site.
* More many other changes.

= 0.4.2 =
* Now you can use tags in the aphorism text
* Errors in export and import are corrected

= 0.4.0 =
* The error with slashes is corrected. It arising on some servers at preservation of templates of a conclusion. Check 'magic_quotes_gpc' is added.
* Possibility to deduce aphorisms on other sites is added.
* Possibility to deduce aphorisms in the admin panel is added.
* The code is slightly simplified, comments to functions are added.

= 0.3.6 =
* Fixed Export and import. Now the aphorism text is located in [CDATA [...]] that allows to use safely any symbols in the aphorism text, for example, "&".
* The point "Remove all" in the list of aphorisms.
* In plug-in archive the POT-file which simplifies localisation, for example, by means of PoEdit is added.

== Screenshots ==

1. The list of aphorisms
2. Aphorismus options
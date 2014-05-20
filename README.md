Merge Javascript CSS Files
=========

Simple class to build and cache a combined file of


Usage
--------------

```php
$cachedFile = Merge::javascript(array(
	"/libs/libexample.js",
	"/libs/libinputs.js",
	"/libs/autocomplete.js",
	"/js/validation.js"
));
```
```html
<script type="text/javascript" language="javascript" src="<?php echo $cachedFile); ?>"></script>
```

Smarty v3.* output filter Plugin
--------------
With this plugin activated, there is no need to make a list of files to cache or even change the script tag to include the cached file, the plugin does it all.

```php
$smarty = new Smarty();
$smarty->loadFilter('output', 'merge');
```

# Prism.js syntax highlighter WordPress plugin
Simple, lightweight plugin that integrates the Prism.js syntax highlighter into WordPress

I wasn't really happy with the few Prism.js WordPress plugins I tried so I decided to put together a quick plugin with the functionality I wanted.

This WordPress plugin depends on [PrismJs by Lea Verou](http://prismjs.com/) for the actual syntax highlighting.
The plugin includes the default PrismJs javascript/css as well as an alternate set of javascript/css with additional language support and functionality

## Default JavaScript/CSS set
The default PrismJs JavaScript and CSS are included in prism.js and prism.css.  The CSS files are for the default theme.
The supported languages are:

- Markup
- CSS
- C-like
- JavaScript

No plugins are included in the default JavaScript/CSS.

## Alternate JavaScript/CSS set
The included custom PrismJs JavaScript and CSS are contained in prism-custom.js and prism-custom.css.  These files include a slightly modified default theme.
The supported languages are:

- Markup
- CSS
- C-like
- JavaScript
- ASP.NET
- Bash
- CSharp (C#)
- HTTP
- JSON
- Markdown
- PHP
- PHP-Extras
- Python
- Sass (SCSS)
- SQL
- TypeScript
- YAML

The following plugins are included in the custom JavaScript/CSS:

- Line Highlight
- Line Numbers
- Autolinker
- File Highlight
- Show Language
- Command Line

## User-defined custom Javascript/CSS set
The user may also use their own customized PrismJS Javascript and CSS set. Simply place the custom .js file in the plugin's `js` directory and place the custom css file in the `css` directory.  Then specify the names of the custom files in the WordPress PrismJs settings (Settings -> Prism.js).

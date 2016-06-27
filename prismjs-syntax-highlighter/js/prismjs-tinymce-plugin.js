(function () {
    /**
     * Escape/Un-escape HTML content.
     *
     * @param {string} content The HTML content to process.
     * @param {boolean} escape Indicates if content should be escaped or not.
     */
    function sanitizeHtml(content, escape) {
        escape = typeof escape !== 'undefined' ? escape : true;

        if (escape) {
            return content.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        else {
            return content.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
        }
    }

    tinymce.create('tinymce.plugins.prismjs', {
        /**
         * Initialize the Prism.js Syntax Highlighter TinyMCE plugin.
         *
         * @param {tinymce.Editor} editor Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (editor, url) {
            editor.addButton('prismjs', {
                text: 'Prism.js',
                title: 'Format code with Prism.js',
                icon: false,
                cmd: 'prismjsCode',
                onPostRender: function () {
                    var button = this;
                    editor.on('NodeChange', function (event) {
                        button.active(event.element.nodeName == 'PRE' || event.element.nodeName == 'CODE');
                    });
                }
            });

            editor.addCommand('prismjsCode', function () {
                var selectedNode = editor.selection.getNode();
                var parentNode = selectedNode.parentNode;
                var selectedReplace = false;

                var currentCode = '';

                // The list of available languages
                // This list is extracted from the current prism.js file
                var languages = [];

                // The language of the code to highlight
                var codeLanguage = '';

                // Indicates if the code to highlight is inline (vs block)
                var codeInline = defaultInline; // Is code inline

                // should code be rendered with line numbers.
                // See http://prismjs.com/plugins/line-numbers/
                var codeLineNumbers = defaultLineNumbers;

                // Code lines to highlight.
                // See http://prismjs.com/plugins/line-highlight/
                var codeHighlightLines = '';

                // Indicates if the code to highlight is command-line
                var codeCommandLine = false;

                // external code source file
                // See http://prismjs.com/plugins/file-highlight/
                var codeSource = '';

                /*
                 * The following variables have been defined in the wordpress plugin according to the user settings:
                 *
                 * defaultLanguage
                 * defaultInline
                 * defaultLineNumbers
                 *
                 */

                // Prism is defined by prism.js
                // Get the list of languages supported by current prism.js file
                // prism.js must be loaded into Admin pages by wordpress plugin for this to work
                if (Prism) {
                    for (var language in Prism.languages) {
                        if (typeof Prism.languages[language] === 'object') {
                            languages.push({text: language, value: language});
                        }
                    }

                    languages.sort(function (a, b) {
                        if (a.text < b.text) {
                            return -1;
                        } else if (a.text > b.text) {
                            return 1;
                        } else {
                            return 0;
                        }
                    });
                }

                // if a CODE element is selected get the current configuration values
                if (selectedNode.nodeName == 'CODE') {
                    currentCode = sanitizeHtml(selectedNode.innerHTML, false);
                    selectedReplace = true;

                    var languageFilter = /\blang(?:uage)?-(?!\*)(\w+)\b/i;
                    codeLanguage = (selectedNode.className.match(languageFilter)) || defaultLanguage;

                    if (parentNode.nodeName == 'PRE') {
                        codeInline = false;  // if <CODE> is wrapped with <PRE> then the code is not inline

                        var parentClasses = ' ' + parentNode.className + ' ';
                        if (parentClasses.indexOf(' line-numbers ') > -1) {
                            codeLineNumbers = true;
                        } else {
                            codeLineNumbers = false;
                        }

                        if (parentClasses.indexOf(' command-line ') > -1) {
                            codeCommandLine = true;
                        } else {
                            codeCommandLine = false;
                        }

                        if (parentNode.hasAttribute('data-line')) {
                            codeHighlightLines = parentNode.getAttribute('data-line');
                        }

                        if (parentNode.hasAttribute('data-src')) {
                            codeSource = parentNode.getAttribute('data-src');
                        }
                    }
                }

                editor.windowManager.open({
                    title: 'Prism.js Syntax Highlighter',
                    body: [
                        {
                            type: 'listbox',
                            name: 'language',
                            label: 'Language:',
                            values: languages,
                            value: codeLanguage[1]
                        },
                        {
                            type: 'checkbox',
                            name: 'commandLine',
                            label: 'Show as command-line:',
                            checked: codeCommandLine
                        },
                        {
                            type: 'checkbox',
                            name: 'inline',
                            label: 'Inline:',
                            checked: codeInline
                        },
                        {
                            type: 'checkbox',
                            name: 'showLineNumbers',
                            label: 'Show line numbers:',
                            checked: codeLineNumbers
                        },
                        {
                            type: 'textbox',
                            name: 'highlightLines',
                            label: 'Highlight lines:',
                            value: codeHighlightLines
                        },
                        {
                            type: 'textbox',
                            name: 'code',
                            label: 'Code:',
                            multiline: true,
                            minWidth: 500,
                            minHeight: 300,
                            value: currentCode
                        },
                        {
                            type: 'textbox',
                            name: 'codeSource',
                            label: 'Source:',
                            value: codeSource
                        }
                    ],

                    onsubmit: function (event) {
                        var html = '';
                        var codeTag = '<code';

                        // build the <code> tag
                        if (event.data.inline) {
                            codeTag += '>';
                        } else {
                            codeTag += ' class="language-' + event.data.language + '">';
                        }

                        codeTag += sanitizeHtml(event.data.code) + '</code>';

                        // build out the remaining html (for code blocks)
                        if (event.data.inline) {
                            html = codeTag;
                        } else {
                            html = '<pre';
                            preClass = [];

                            // check for class selectors
                            if (event.data.commandLine) {
                                preClass.push('command-line');
                            }

                            if (event.data.showLineNumbers) {
                                preClass.push('line-numbers');
                            }

                            if (preClass.length > 0) {
                                html += ' class="' + preClass.join(' ') + '"';
                            }

                            if (event.data.highlightLines) {
                                html += ' data-line="' + event.data.highlightLines + '"';
                            }

                            if (event.data.codeSource) {
                                html += ' data-src="' + event.data.codeSource + '"';
                            }

                            html += '>';
                            html += codeTag;
                            html += '</pre>';
                        }

                        if (selectedReplace) {
                            editor.dom.remove(selectedNode.parentNode);
                        }

                        editor.insertContent(html);
                        editor.selection.setCursorLocation(editor.selection.getNode().firstChild); // Select <code> instead of <pre> (or <code> instead of <p> if <pre> doesn't exist)
                    }
                });
            });
        },

        /**
         * Return information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'Prism.js Syntax Highlighter WordPress plugin',
                author: 'Jody Boucher',
                authorurl: 'http://jodyboucher.com',
                infourl: 'https://github.com/jodyboucher/prismjs-syntax-highlighter-wp-plugin',
                version: '1.0.0'
            };
        }
    });

    tinymce.PluginManager.add('prismjs', tinymce.plugins.prismjs);
})();

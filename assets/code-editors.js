(function ($) {
    'use strict';
    $(function () {
        if (!window.wp || !wp.codeEditor) {
            return;
        }
        $.each(window.pccCodeEditors || {}, function (id, settings) {
            var textarea = document.getElementById(id);
            if (!textarea) {
                return;
            }
            settings.onTabNext = function () {
                $(textarea.form).find('input[type="submit"], button[type="submit"]').first().trigger('focus');
            };
            settings.onTabPrevious = function () {
                toolbar.find('button').trigger('focus');
            };
            var editor = wp.codeEditor.initialize(textarea, settings).codemirror;
            var keys = $.extend({}, editor.getOption('extraKeys'), {
                Tab: function (cm) {
                    if (cm.somethingSelected()) {
                        cm.indentSelection('add');
                    } else {
                        var count = cm.getOption('indentUnit');
                        cm.replaceSelection(new Array(count + 1).join(' '), 'end', '+input');
                    }
                },
                'Shift-Tab': 'indentLess'
            });
            editor.setOption('extraKeys', keys);
            editor.setSize('100%', id === 'pcc_php_code' ? 380 : 300);
            var label = document.querySelector('label[for="' + id + '"]');
            editor.getInputField().setAttribute('aria-label', label ? label.textContent : 'Custom code');
            var toolbar = $('<div class="pcc-editor-toolbar"></div>');
            $('<button type="button" class="button button-small">Reindent code</button>')
                .on('click', function () {
                    editor.operation(function () {
                        for (var line = 0; line < editor.lineCount(); line++) {
                            editor.indentLine(line, 'smart');
                        }
                    });
                    editor.focus();
                }).appendTo(toolbar);
            $('<span>4-space indentation · Tab / Shift+Tab to indent · Esc then Tab to leave editor</span>').appendTo(toolbar);
            toolbar.insertBefore(editor.getWrapperElement());
            // Ensure each existing save form receives the latest editor contents.
            $(textarea.form).on('submit.pccCodeEditor', function () {
                editor.save();
            });
        });
    });
})(jQuery);

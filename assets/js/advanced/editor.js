const $ = window.jQuery;

export function editor(obj) {
    let settings;
    if ($.isFunction($.fn.redactor)) {

        settings = {
            plugins: [],
            imagePosition: {
                "left": "alignleft",
                "right": "alignright",
                "center": "aligncenter"
            },
            lang: window.TypeRocket.redactor.lang | 'en',
            multipleUpload: false,
            imageResizable: true,
        };

        if (!$.isEmptyObject(window.TypeRocket.redactor.override)) {
            settings = window.TypeRocket.redactor.override;
        }

        if (!$.isEmptyObject(window.TypeRocket.redactor.extend)) {
            settings = Object.assign(settings, window.TypeRocket.redactor.extend);
        }

        if (window.TypeRocket.redactor.plugins.length > 0) {
            settings.plugins = window.TypeRocket.redactor.plugins;
        }

        $(obj).find('.tr-editor').each(function() {
            let el = $(this);

            if (el.parent().hasClass('redactor-box')) {
                console.log('replacing redactor');
                el.off();
                el = el.clone();
                let pc = $(this).parent().parent();

                // remove none clone instance
                $(this).parent().remove();

                pc.append(el);

                // not working
                // el.redactor('destroy');
                // needs a selector not a ref
            }

            if(el.attr('name')) {
                if(el.attr('data-settings')) {
                    settings = Object.assign(settings, JSON.parse(el.attr('data-settings')));
                }
                el.redactor(settings);
            }
        });
    }
}
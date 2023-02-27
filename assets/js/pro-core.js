import {TypeRocketConditions} from "./advanced/conditions";
import {editor} from "./advanced/editor";

const { __ } = wp.i18n;

;jQuery(function($) {
    [editor].forEach(function(caller) {
        caller($(document));
        TypeRocket.repeaterCallbacks.push(caller);
    });

    new TypeRocketConditions('name', $);

    $(document).on('input blur change', '.tr-component-nameable-input', function() {
        let $that = $(this);
        let $parent = $that.closest('[data-tr-component]').first();

        if( $parent ) {
            let hash = $parent.attr('data-tr-component');
            let value = $that.val() ? $that.val() : $that.attr('placeholder');
            $('[data-tr-component-tile='+hash+'] .tr-builder-component-title').first().text(value);
        }
    });

    $(document).on('blur keyup change', '.tr-has-conditionals', function(e) {
        let $that = $(this);

        let fn = () => {
            let bound = $that.data('tr-conditions-bond') ? $that.data('tr-conditions-bond') : [];
            for(let b in bound) {
                bound[b].trigger('condition');
            }
        };

        if(e.type === 'keyup') {
            window.trUtil.delay(fn, 250);
        } else {
            fn()
        }
    });

    $(document).on('input', '.tr-range-input', function(e) {
        e.preventDefault();
        $(this).prev().find('span').html($(this).val());
    });

    $(document).on('paste', '.tr-input-textexpand', function(e) {
        let $that = $(this);
        let paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text');

        const selection = window.getSelection();
        if (!selection.rangeCount) return false;
        selection.deleteFromDocument();
        selection.getRangeAt(0).insertNode(document.createTextNode(paste));

        e.preventDefault();

        $that.next().val($that.html()).trigger('change');
    });

    $(document).on('blur keyup change input', '.tr-input-textexpand', function(event) {
        let $that = $(this);

        let tmp = document.createElement("div");
        tmp.innerHTML = $that.html();
        let content = tmp.textContent || tmp.innerText || "";

        $that.next().val(content).trigger('change');
    });

    $(document).on('keydown', '.tr-input-textexpand', function(event) {
        let $that = $(this);

        if (event.which == '13') {
            return false;
        }
    });

    $(document).on('click', '.typerocket-elements-fields-textexpand .tr-label', function() {
        let hidden_input = $(this).attr('for');

        $('#' + hidden_input).prev().focus();
    });

    if (jQuery('.builder-field-group') && window.TypeRocket && window.TypeRocket.pageBuilderPlus) {
        jQuery(document).on('click', '.tr-save-component-as-block', function (e) {
            const parent = jQuery(e.target).closest('.tr-component-inputs');
            let formInputs = parent.find(':input').serialize();
            const data = new URLSearchParams(formInputs);
            data.append('_tr_nonce_form', window.trHelpers.nonce);
            data.append('tr[_save_component_as_block]', true);
            let url = window.trUtil.makeUrlHttpsMaybe(window.trHelpers.site_uri + '/tr-api/block');

            jQuery.typerocketHttp.send('POST', url, data);
            e.preventDefault();
        });
    }
});
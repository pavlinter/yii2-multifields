/**
 * @copyright Copyright &copy; Pavels Radajevs, 2014
 * @package yii2-multifields
 * @version 1.0.1
 */
(function($) {

    $.fn.multiFields = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.multiFields');
            return false;
        }
    };

    var defaults = {
        postName: "id",
        excludeSelector: ".exclude",
        emptySelector: ".empty",
        parentClass: "copyRow",
        appendTo: false,
        limit: 0, // 0 = unlimited
        uniqId: 0,
        attributes: [],
        template: false,
        index: 'index',
        form: false,
        closeButtonClass: '',
        extData: {},
        deleteRouter: '',
        dataType: 'json',
        requiredRows: 1,
        beforeSendDelete: function($row,$form){
            $row.hide();
        },
        deleteCallback: function(data,$row,$form){
            var settings = this;
            if(data.r){
                $row.remove();
                $form.trigger("removedRow.mf", [settings, true]); //true == deleted from database
            }else{
                $row.show();
            }
        },
        completeDelete: function(parent,form){},
        confirmMessage: '',
        confirmCancelCallback: function(parent,form){},
        confirmCallback: function(message){
            return confirm(message);
        },
        inputSavedClass: 'mf-field-saved',
        inputFlyClass: 'mf-field-fly',
        parentSavedClass: 'mf-row-saved',
        parentFlyClass: 'mf-row-fly',
        afterAppend: function(clone){}
    };

    var attributeDefaults = {
        // a unique ID identifying an attribute (e.g. "loginform-username") in a form
        id: undefined,
        // attribute name or expression (e.g. "[0]content" for tabular input)
        name: undefined,
        // the jQuery selector of the container of the input field
        container: undefined,
        // the jQuery selector of the input field under the context of the container
        input: undefined,
        // the jQuery selector of the error tag under the context of the container
        error: '.help-block',
        // whether to encode the error
        encodeError: true,
        // whether to perform validation when a change is detected on the input
        validateOnChange: true,
        // whether to perform validation when the input loses focus
        validateOnBlur: true,
        // whether to perform validation when the user is typing.
        validateOnType: false,
        // number of milliseconds that the validation should be delayed when a user is typing in the input field.
        validationDelay: 500,
        // whether to enable AJAX-based validation.
        enableAjaxValidation: false,
        // function (attribute, value, messages), the client-side validation function.
        validate: undefined,
        // status of the input field, 0: empty, not entered before, 1: validated, 2: pending validation, 3: validating
        status: 0,
        // whether the validation is cancelled by beforeValidateAttribute event handler
        cancelled: false,
        // the value of the input
        value: undefined
    };

    var methods = {
        init: function (options) {


            return this.each(function () {


                var settings        = $.extend({}, defaults, options || {}),
                    uniqId          = settings.uniqId;
                settings.limit      = parseInt(settings.limit);
                var $form = $(settings.form),
                    $btn  = $(this),
                    data = $form.data('mf');

                //data-mf-uniq
                if(!data){
                    $(document).on('updateErrors.mf',settings.form,function(e,errors){

                        if (!errors || errors.length == 0) {
                            return false;
                        }
                        var data  = $(this).yiiActiveForm('data');
                        $.each(data.attributes, function (i,attribute) {
                            if (errors[attribute.id]) {
                                $(attribute.error,attribute.container).text(errors[attribute.id][0]);
                                $(attribute.container).removeClass(data.settings.validatingCssClass + ' ' + data.settings.successCssClass)
                                    .addClass(data.settings.errorCssClass);
                            }
                        });
                        return true;
                    }).on('updateRows.mf',settings.form,function(e,o){
                        var i,field,inp,cont;
                        for (i in o) {

                            field = o[i];
                            inp = $('#' + field.id);
                            cont = inp.closest('.' + settings.parentClass).attr('data-mf-uniq', field.uniq);

                            inp.attr({
                                'data-mf-uniq':field.uniq,
                                'name':field.newName
                            }).removeClass(settings.inputFlyClass).addClass(settings.inputSavedClass);
                            cont.addClass(settings.parentSavedClass).removeClass(settings.parentFlyClass);
                        }

                    }).on('scrollToError.mf',settings.form,function(e,options){

                        var $this   = $(this);
                        var settings = $.extend({
                            options: {},
                            body: 'html ,body',
                            minusHeight: $(window).height()/3
                        },settings || {});
                        options = $.extend({
                            duration: 1000
                        },settings.options);

                        var data    = $this.yiiActiveForm('data');
                        var $error = $this.find('.'+data.settings.errorCssClass+':first');
                        if($error.length){
                          $(settings.body).animate({
                            scrollTop: $error.offset().top - settings.minusHeight
                          },options);
                        }

                    }).on('scrollToTop.mf',settings.form,function(e,settings){

                        settings = $.extend({
                            options: {},
                            body: 'html ,body',
                            minusHeight: $(window).height()/3,
                            trigger: this
                        },settings || {});
                        var options = $.extend({
                            duration: 1000
                        },settings.options);

                        var top     = $(settings.trigger).offset().top - settings.minusHeight;

                        $(settings.body).animate({
                            scrollTop: top
                        },options);

                    });
                }

                if (!data) {
                    $form.data('mf', {
                        target : $(this),
                        settings : settings
                    });
                }

                $('.'+settings.parentClass+' .'+settings.closeButtonClass).on('click.mf',{settings:settings},deleteRow);
                $('.'+settings.parentClass+' :input').each(function(){
                    var el = $(this);
                    if(el.attr('data-mf-uniq') < 0){
                        el.addClass(settings.inputFlyClass);
                        el.closest('.'+settings.parentClass).addClass(settings.parentFlyClass);
                    }else{
                        el.closest('.'+settings.parentClass).addClass(settings.parentSavedClass);
                        el.addClass(settings.inputSavedClass);
                    }
                });

                // set click action
                $btn.on('click.mf',function(e,ID){
                    ID = ID || uniqId--;

                    var counter = $(settings.parentClass).length;
                    // stop limit
                    if (settings.limit != 0 && counter >= settings.limit) {
                        return false;
                    }

                    var $this   = $(this);
                        $form   = $(settings.form),
                        pattern = new RegExp(settings.index,'g'),
                        clone   = $(settings.template.replace(pattern,ID)).addClass(settings.parentFlyClass);


                    $('.'+settings.closeButtonClass,clone).on('click.mf',{settings:settings},deleteRow);

                    //Remove Elements with excludeSelector
                    if (settings.excludeSelector){
                        $(clone).find(settings.excludeSelector).remove();
                    }
                    //Empty Elements with emptySelector
                    if (settings.emptySelector){
                        $(clone).find(settings.emptySelector).empty();
                    }

                    var event = $.Event("beforeAppend.mf");
                    $this.trigger(event, [clone, settings]);
                    if(event.result !== false) {
                        if (settings.appendTo) {
                            $(settings.appendTo).append(clone);
                        } else {
                            var $last = $('.' + settings.parentClass + ':last');
                            if(!$last.length){
                                alert('The "appendTo" property must be set.');
                            }
                            $last.after(clone);
                        }
                    }

                    //clear input
                    $(':input',clone).not(':button, :submit, :reset, :hidden')
                        .addClass(settings.inputFlyClass)
                        .val('')
                        .removeAttr('checked')
                        .removeAttr('selected');

                    $.each(settings.attributes,function(i,attribute){
                        attribute = $.extend(attributeDefaults,attribute);//copy object
                        attribute.container = attribute.container.replace(pattern,ID);
                        attribute.error     = attribute.error.replace(pattern,ID);
                        attribute.input     = attribute.input.replace(pattern,ID);
                        attribute.id        = attribute.id.replace(pattern,ID);
                        attribute.name      = attribute.name.replace(pattern,ID);

                        $form.yiiActiveForm('add', attribute);
                    });
                    $this.trigger('afterAppend.mf', [clone, settings]);
                    return false;
                }); // end click action


                var $rows = $('.' + settings.parentClass);

                if(settings.requiredRows < 1) {
                    $rows.filter("." + settings.parentFlyClass).each(function(){
                        $(this).find("." + settings.inputFlyClass).each(function(){
                            $form.yiiActiveForm('remove', $(this).attr("id"));
                        });
                    }).remove();
                } else if (settings.requiredRows > $rows.length) {
                    var count =  settings.requiredRows - $rows.length;
                    for (var i = 0; i < count; i++) {
                        $btn.trigger('click.mf');
                    }
                }


            });
        },

        destroy: function () {
            return this.each(function () {
                $(this).unbind('.mf');
            });
        }
    };
    /**
     * Performs deleting row
     */
    var deleteRow = function(e) {
        var $this       = $(this),
            settings    = e.data.settings,
            $form       = $(settings.form),
            $row        = $this.closest('.'+settings.parentClass),
            $inputs     = $row.find(':input'),
            uniq        = $row.attr('data-mf-uniq'),
            result      = undefined;

        if(!uniq){
            return false;
        }
        if($row.is('.' + settings.parentSavedClass)){
            if($('.' + settings.parentSavedClass).size() <= settings.requiredRows){
                return false;
            }
        } else {
            if($('.' + settings.parentClass).size() <= settings.requiredRows){
                return false;
            }
        }

        $inputs.each(function(){
            $form.yiiActiveForm('remove', $(this).attr("id"));
        });
        if(uniq < 0){
            $this.trigger("afterRemove.mf", [$row, settings]);
            $row.remove();
            $form.trigger("removedRow.mf", [settings, false]); //true == deleted from database
            return false;
        }
        result = settings.confirmCallback(settings.confirmMessage);
        confirmCallback(settings,uniq,result,$form,$row);
        return false;
    };
    /**
     * Performs request to server after delete fields
     * @param object settings
     * @param int uniq
     * @param boolean result
     * @param object $form
     */
    var confirmCallback = function (settings, uniq, result, $form, $row) {
        if (result) {
            var data,
                extData = $.extend({}, settings.extData || {});
            extData[settings.postName] = uniq;
            data = $form.serialize() + '&' + $.param(extData);

            $.ajax({
                url: settings.deleteRouter,
                type: "POST",
                dataType: settings.dataType,
                data: data,
                beforeSend: function () {
                    settings.beforeSendDelete($row, $form);
                }
            }).done(function (d) {
                settings.deleteCallback(d, $row, $form);
            }).always(function (jqXHR, textStatus) {
                settings.completeDelete($row, $form, textStatus);
            }).fail(function (jqXHR, textStatus, errorThrown) {
                $row.show();
                alert(errorThrown.toString());
            });

        } else {
            settings.confirmCancelCallback($row, $form);
        }

    };
})(jQuery);


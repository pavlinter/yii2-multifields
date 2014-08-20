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
            if(data.r){
                $row.remove();
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

    var methods = {
        init: function (options) {
            return this.each(function () {


                var settings        = $.extend({}, defaults, options || {}),
                    uniqId          = settings.uniqId;
                settings.limit      = parseInt(settings.limit);
                var $form = $(settings.form),
                    $btn  = $(this),
                    data = $form.data('mf');

                if(!data){
                    $(document).on('updateErrors.mf',settings.form,function(e,errors){

                        if (!errors || errors.length == 0) {
                            log('Error empty!');
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
                    }).on('updateRows.mf',settings.form,function(e,o){
                        var i,field,inp,cont;
                        for (i in o) {

                            field = o[i];
                            inp = $('#' + field.id);
                            cont = inp.closest('.' + settings.parentClass);

                            inp.attr({
                                'data-mf-uniq':field.uniq,
                                'name':field.newName
                            }).removeClass(settings.inputFlyClass).addClass(settings.inputSavedClass);
                            cont.addClass(settings.parentSavedClass);
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
                    if(el.attr('data-mf-uniq')<0){
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

                    var $form   = $(settings.form),
                        formSettings = $form.yiiActiveForm('data'),
                        pattern = new RegExp(settings.index,'g'),
                        clone   = $(settings.template.replace(pattern,ID));

                    $('.'+settings.closeButtonClass,clone).on('click.mf',{settings:settings},deleteRow);





                    //Remove Elements with excludeSelector
                    if (settings.excludeSelector){
                        $(clone).find(settings.excludeSelector).remove();
                    }
                    //Empty Elements with emptySelector
                    if (settings.emptySelector){
                        $(clone).find(settings.emptySelector).empty();
                    }


                    $.each(settings.attributes,function(i,attribute){
                        attribute = $.extend({},attribute);//copy object
                        attribute.container = attribute.container.replace(pattern,ID);
                        attribute.error     = attribute.error.replace(pattern,ID);
                        attribute.input     = attribute.input.replace(pattern,ID);
                        attribute.id        = attribute.id.replace(pattern,ID);
                        attribute.name      = attribute.name.replace(pattern,ID);

                        formSettings.attributes[attribute.name] = attribute;
                    });

                    if(settings.appendTo){
                        $(settings.appendTo).append(clone);
                    }else{
                        $('.'+settings.parentClass+':last').after(clone);
                    }

                    //clear input
                    $(':input',clone).not(':button, :submit, :reset, :hidden')
                        .addClass(settings.inputFlyClass)
                        .val('')
                        .removeAttr('checked')
                        .removeAttr('selected');

                    settings.afterAppend(clone);
                    saveFormSettings(settings.form,formSettings);
                    return false;

                }); // end click action

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
        var settings = e.data.settings,
            $form = $(settings.form),
            $row = $(this).closest('.'+settings.parentClass),
            $inputs =  $row.find(':input'),
            uniq = undefined,
            result = undefined,
            val = undefined;

        $inputs.each(function(){
            val = $(this).attr('data-mf-uniq');
            if(val >= 0){
                uniq = val;
                return false;
            }
            uniq = val;
        });

        if(uniq === undefined){
            return false;
        }

        if($('.'+settings.parentClass).size() <= settings.requiredRows){
            return false;
        }

        if(uniq < 0){
            $row.remove();
            deleteValidation(settings,$inputs);
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

            jQuery.ajax({
                url: settings.deleteRouter,
                type: 'POST',
                data: data,
                dataType: settings.dataType,
                beforeSend: function () {
                    settings.beforeSendDelete($row, $form);
                },
                success: function (d) {
                    settings.deleteCallback(d, $row, $form);
                },
                complete: function () {
                    settings.completeDelete($row, $form);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    parent.show();
                    alert(errorThrown.toString());
                }
            });
        } else {
            settings.confirmCancelCallback($row, $form);
        }

    };

    /**
     * Performs destruction event fields
     * @param object settings
     * @param object $inputs
     */
    var deleteValidation = function(settings,inputs) {
        var inputIds = {},
            formSettings = $(settings.form).yiiActiveForm('data');
        $.each(inputs,function(){
            inputIds[$(this).attr('id')] = true;
        });
        $.each(formSettings.attributes,function(key,value) {
            if(inputIds[this.id]) {
                delete formSettings.attributes[key];
            }
        });
        saveFormSettings(settings.form,formSettings);
    };
    /**
     * Set new settings form
     * @param string form selector
     * @param object new settings form
     */
    var saveFormSettings = function(form,formSettings) {
        $(form).yiiActiveForm('destroy').yiiActiveForm(formSettings.attributes,formSettings.settings);
    };
    /**
     * Show message in console
     * @param string msg
     */
    var log = function (msg) {
        if (typeof console === "undefined" || typeof console.log === "undefined") {
            alert(msg);
        }else{
            console.log(msg);
        }
    };


})(jQuery);


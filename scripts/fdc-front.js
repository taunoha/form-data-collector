window.fdc = window.fdc || {};

jQuery(function($) {

    var data = null;
    var settings = typeof _fdcVars === 'undefined' ? {} : _fdcVars;

    var _isObject = function(variable) {
        return ( typeof variable === 'function' || typeof variable === 'object' ) && !!variable;
    };

    var _deleteEmptyFiles = function(form) {
        form.find('[type="file"]').each(function(i, el) {
            if( el.files.length == 0 ) {
                data.delete(el.name);
                data.append(el.name, settings.str.no_file_added);
            }
        });
    };

    fdc.ajax = {
        settings: settings.ajax || {},

        /**
         * fdc.ajax.post([formID | jQuery object | Javascript object], [options])
         *
         * Sends a POST request to WordPress.
         *
         * @since 2.0.0             Parameter 'form' can be also Javascript object
         * @since 2.0.0             Added support for file upload
         * @since 1.2.0
         *
         * @param  {string|object}  Selector, jQuery object or Javascript object
         * @param  {object}         The options passed to jQuery.ajax.
         * @return {$.promise}      A jQuery promise that represents the request, decorated with an abort() method.
         */
        post: function(form, options) {
            var promise, deferred;

            if( form instanceof $ ) {
                data = new FormData(form.get(0));
                _deleteEmptyFiles(form);
            } else if( form instanceof String ) {
                data = new FormData($(form).get(0));
                _deleteEmptyFiles($(form));
            } else if( form instanceof Object ) {
                data = form;
            }

            if( null === data ) {
                throw new Error('Data is missing.');
                return;
            }

            if( data instanceof FormData ) {

                data.append('action', 'fdc_action');
                data.append('cmd', 'save');
                data.append('check', settings.ajax.nonce);
                data.append('fdcUtility', true);

                options = $.extend({
                    cache: false,
                    processData: false,
                    contentType: false
                }, options);

            } else {

                data = {
                    action: 'fdc_action',
                    cmd: 'save',
                    check: settings.ajax.nonce,
                    fdcUtility: true,
                    data: form
                };

            }

            options = $.extend({
                type: 'POST',
                url: settings.ajax.url,
                context: this,
                data: data
            }, options);

            deferred = $.Deferred(function(deferred) {
                if ( options.success ) {
                    deferred.done( options.success );
                }
                if ( options.error ) {
                    deferred.fail( options.error );
                }

                delete options.success;
                delete options.error;
                delete options.form;

                deferred.jqXHR = $.ajax(options).done(function(response) {
                    if( _isObject(response) ) {
                        deferred[ response.success ? 'resolveWith' : 'rejectWith' ]( this, [response.data] );
                    } else {
                        deferred.rejectWith( this, [response] );
                    }
                }).fail( function() {
                    deferred.rejectWith( this, arguments );
                });
            });

            promise = deferred.promise();
            promise.abort = function() {
                deferred.jqXHR.abort();
                return this;
            };

            return promise;
        }
    };

});

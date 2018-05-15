jQuery(function($) {

    'use strict';

    var FDC = {
        $target: null,
        itemID: 0,
        action: null,
        init: function() {
            $(document).on('click', '[data-action]', FDC.toAction);
        },
        toAction: function() {
            FDC.$target = $(this);
            FDC.action = FDC.$target.data('action');
            FDC.itemID = FDC.$target.data('id');

            switch( FDC.action )
            {
                case 'delete' : FDC.toDelete();  break;
            }
        },
        toDelete: function() {
            $('#fdc-delete-modal').dialog({
                title: _fdcVars.str.delete_dialog_title.replace('{#}', FDC.itemID),
                dialogClass: 'wp-dialog',
                modal: true,
                height: 'auto',
                width: 400,
                resizable: false,
                buttons: [
                    {
                        text: _fdcVars.str.delete_this_entry,
                        click: function() {
                            var $self = $(this);
                            var forceDelete = $self.find('input[name="fdcForceDelete"]').first().prop('checked');

                            wp.ajax.send('fdc_action', {
                                data: {
                                    check: _fdcVars.ajax.nonce,
                                    id: FDC.itemID,
                                    cmd: FDC.action,
                                    force: forceDelete,
                                    fdcUtility: true
                                },
                                success: function(data) {
                                    FDC.$target.closest('tr').fadeOut();
                                    $self.dialog('close');
                                    $self.find('.notice-warning').remove();
                                    $self.find('form').get(0).reset();
                                },
                                error: function(data) {
                                    $self.find('.notice-warning').remove();
                                    $self.find('form').first().append('<div class="notice notice-warning" style="margin: 0; background-color: #fbf8ee"><p>' + data + '</p></div>');
                                }
                            });

                        }
                    },
                    {
                        text: _fdcVars.str.cancel,
                        click: function() {
                            $(this).dialog('close');
                            $(this).find('form').get(0).reset();
                            $(this).find('.notice-warning').remove();
                        }
                    }
                ]
            });
        },
    };

    FDC.init();

});

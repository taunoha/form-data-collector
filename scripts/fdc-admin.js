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
            if( window.confirm("Do you really want to delete this entry?") ) {
                $.post(ajaxurl, { action: 'fdc_action', check: _fdcVars.ajax.nonce, id: FDC.itemID, cmd: FDC.action, fdcUtility: true }, function(data) {
                    FDC.$target.closest('tr').fadeOut();
                });
            }
        },
    };

    FDC.init();

});

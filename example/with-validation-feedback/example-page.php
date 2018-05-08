<?php defined('ABSPATH') or die(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Data Collector</title>
</head>
<body>
    <form action="<?php echo esc_url(get_permalink()); ?>" method="post">
        <input type="hidden" name="formID" value="feedback">
        <input type="hidden" name="fromURL" value="<?php echo esc_url(get_permalink()); ?>">
        <div style="position: absolute; left: -1000em;"><div class="form-group"><input type="text" name="honeypot" value="" /></div></div>
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="form-group">
            <label>File</label>
            <input class="form-control" type="file" id="file" name="file" accept="application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf,application/zip,application/octet-stream">
        </div>
        <div class="form-group">
            <label>Files</label>
            <input class="form-control" type="file" multiple id="files" name="files[]" accept="application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf,application/zip,application/octet-stream">
        </div>
        <button type="button" class="btn btn-secondary" data-fdc-action="submit">Submit</button>
    </form>
    <script type="text/javascript">
        jQuery(function($) {
            var fdcFormHandler = function(e) {
                var $form = $(this).closest('form');
                e.preventDefault();

                fdc.ajax.post($form, {
                    error: function(data) {
                        console.log(data);
                    },
                    success: function(data) {
                        console.log(data);
                    }
                });

            };

            $('[data-fdc-action="submit"]').on('click', fdcFormHandler);
        });
    </script>
</body>
</html>

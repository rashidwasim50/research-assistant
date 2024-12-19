jQuery(document).ready(function ($) {
    let mediaUploader;

    // Media Upload Button
    $('#upload_logo_button').on('click', function (e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Logo',
            button: { text: 'Use This Logo' },
            multiple: false,
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#logo_preview').html(`
                <div class="media-item">
                    <img src="${attachment.url}" alt="" style="max-width: 200px;">
                    <span class="remove-media" style="cursor: pointer; color: red;">&times;</span>
                </div>
            `);
            $('#ai_research_assistant_logo').val(attachment.url); // Save URL to hidden input
        });

        mediaUploader.open();
    });

    // Remove Media
    $(document).on('click', '.remove-media', function () {
        $('#logo_preview').empty();
        $('#ai_research_assistant_logo').val('');
    });
});

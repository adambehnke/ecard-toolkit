jQuery(document).ready(function($) {
    $('.modal-ecard .close-modal, .modal-ecard .confirm').on('click.fadeOut', function() {
        var trigger = $(this);
        var modal = trigger.closest('.modal-ecard');

        modal.fadeOut(400, function() {
            modal.removeClass('active processing has-items-to-delete');
            modal.find('.empty-on-close').empty();
        });
    });

    $('.modal-ecard .confirm').on('click.statusAnimation', function() {
        var trigger = $(this);
        var modal = trigger.closest('.modal-ecard');

        modal.addClass('active processing');
    });

    $(document).on('click', '.trigger-modal', function() {
        var trigger = $(this);
        var modalClass = trigger.attr('data-modal');
        var modal = $('.' + modalClass);

        modal.fadeIn();
    });
});

jQuery(document).ready(function($) {
    $('.order-search-box').on('click', 'button', function(e) {
        e.preventDefault();

        let button = $(this);
        let searchBox = button.closest('.order-search-box');

        processSearch(searchBox);
    });

    $('input[name="order_search"').keypress(function(e) {
        if(e.which == 13) {
            e.preventDefault();
            e.stopPropagation();

            let input = $(this);
            let searchBox = input.closest('.order-search-box');

            processSearch(searchBox);
        }
    });

    function processSearch(searchBox) {
        let location = '';
        let html = '';
        let content = $('#wpbody-content');
        let button = searchBox.find('button');
        let searchInput = searchBox.find('input');
        let searchTerm = searchInput.val();
        let sanitizedSearchTerm = encodeURIComponent(searchTerm);
        let wrap = content.find('.wrap');
        let screenMetaLinks = $('.screen-meta-links');

        html += '<div class="searching-animation"><i class="fa fa-search icon-search"></i>';
        html += '<span class="searching-text">Searching for <span class="search-term">';
        html += searchTerm;
        html += '</span></span></div>';

        screenMetaLinks.fadeOut(400);
        wrap.fadeOut(400);
        content.append(html);

        button.prop('disabled', true);
        button.addClass('active');
        button.find('span').text('Searching');

        window.scrollTo(0, 0);

        location  = orderPageSearch.homeUrl + '/wp-admin/edit.php?s=';
        location += sanitizedSearchTerm;
        location += '&post_status=all&post_type=shop_order&action=-1&m=0&_customer_user&paged=1&action2=-1';

        window.location = location;
    }
});
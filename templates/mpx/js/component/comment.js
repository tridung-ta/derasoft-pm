$(document).ready(function () {
    const wrapper    = $('#review-dynamic-wrapper');
    const OBJECT_ID = parseInt(wrapper.data('pid'));
    const PER_PAGE   = parseInt(wrapper.data('per-page'));

    let currentPage = 1;
    let currentStar = 0;

    function loadReviews(page, star) {
        currentPage = page;
        currentStar = star;

        $('#review-dynamic').css('opacity', '0.4');

        $.ajax({
            url: '/ajax.php',
            method: 'GET',
            data: {
                op:   'get_reviews',
                pid:  OBJECT_ID,
                page: page,
                star: star
            },
            success: function (html) {
                $('#review-dynamic').html(html).css('opacity', '1');
                bindPaginationEvents();
                $('html, body').animate({
                    scrollTop: $('#review-dynamic').offset().top - 140
                }, 300);
            },
            error: function () {
                $('#review-dynamic').css('opacity', '1');
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    }

    function bindPaginationEvents() {
        $('#review-dynamic')
            .off('click', '.page-btn[data-page]')
            .on('click', '.page-btn[data-page]', function () {
                loadReviews(parseInt($(this).data('page')), currentStar);
            });
    }

    $('#review-filter-btns').on('click', '.filter-btn', function () {
        $('#review-filter-btns .filter-btn').removeClass('active');
        $(this).addClass('active');
        loadReviews(1, parseInt($(this).data('star')));
    });

    bindPaginationEvents();
});
let searchAjax = null;
let isSearching = false;

function toggleSearchLoading(state) {
    isSearching = state;
    const wrapper = $("#search-results-wrapper");
    const submitBtn = $(".search-submit");
    if (state) {
        submitBtn.prop("disabled", true);
        wrapper.addClass("loading");
        if (!wrapper.find(".search-loading").length) {
            wrapper.append(`
                <div class="search-loading flex center-hor center-ver item-padding-20">
                    <p>Đang tải dữ liệu...</p>
                </div>
            `);
        }
    } else {
        submitBtn.prop("disabled", false);
        wrapper.removeClass("loading");
        wrapper.find(".search-loading").remove();
    }
}

function Search(page = 1) {
    const searchInput = document.getElementById("sidebar-search-input");
    if (!searchInput || isSearching) return;

    const searchQuery = searchInput.value.trim();
    if (!searchQuery) return;

    const isDetail = searchInput.dataset.isDetail === "1";
    const categorySlug = searchInput.dataset.categorySlug || "";

    // Nếu đang ở trang detail → redirect sang trang category kèm query param
    if (isDetail && categorySlug) {
        const params = new URLSearchParams({
            search: searchQuery,
            page: page
        });
        window.location.href = "/" + categorySlug + "?" + params.toString();
        return;
    }

    // Nếu đang ở trang list → AJAX bình thường
    if (searchAjax) {
        searchAjax.abort();
    }
    toggleSearchLoading(true);

    searchAjax = $.ajax({
        url: "/ajax.php",
        method: "GET",
        data: {
            op: "searchkw",
            searchQuery,
            idCate: searchInput.dataset.idCate || 0,
            lang: searchInput.dataset.lang || "vn",
            type: searchInput.dataset.type || "products",
            page
        },
        success: function (data) {
            $("#search-results-wrapper").html(data);
        },
        error: function (xhr, status, error) {
            if (status !== "abort") {
                console.error("Search AJAX Error:", error);
                $("#search-results-wrapper").html(`
                    <div class="item-padding-20 text-center">
                        Có lỗi xảy ra, vui lòng thử lại.
                    </div>
                `);
            }
        },
        complete: function () {
            toggleSearchLoading(false);
            searchAjax = null;
        }
    });
}

$(document).ready(function () {
    $("#sidebar-search-input").on("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            Search(1);
        }
    });

    $(".search-submit").on("click", function () {
        Search(1);
    });

    $(document).on("click", "[data-ajax='1'] a.pagination-btn", function (e) {
        e.preventDefault();
        if (isSearching) return;
        const href = $(this).attr("href") || "";
        const match = href.match(/[?&]page=(\d+)/);
        Search(match ? parseInt(match[1]) : 1);
    });

    // Tự động trigger search nếu URL có ?search=... (khi được redirect từ trang detail)
    const urlParams = new URLSearchParams(window.location.search);
    const searchFromUrl = urlParams.get("search");
    const pageFromUrl = parseInt(urlParams.get("page")) || 1;

    if (searchFromUrl) {
        const searchInput = document.getElementById("sidebar-search-input");
        if (searchInput) {
            searchInput.value = searchFromUrl;
            Search(pageFromUrl);
        }
    }
});
$(document).ready(function () {
    select2('searchFrmUserIdJs', fcom.makeUrl('Users', 'autoComplete'), { 'joinShop': 1, 'user_is_supplier': 1 });
    $("#prodcatIdJs").select2({
        dropdownParent: $("#prodcatIdJs").closest('form'),
        allowClear: true,
        placeholder: $("#prodcatIdJs").attr('placeholder')
    }).on('select2:open', function(e) {        
        $("#prodcatIdJs").data("select2").$dropdown.addClass("custom-select2 custom-select2-single");
    }).data("select2").$container.addClass("custom-select2-width custom-select2 custom-select2-single");

});


document.addEventListener("DOMContentLoaded", function () {

    const form = document.forms.frmRecordSearch;
    const pagingForm = document.forms['frmRecordSearchPaging'] || form;

    /* ===============================
       1. DECODE URL PROPERLY
    =============================== */
    let rawQuery = window.location.search;
    rawQuery = decodeHtmlParams(rawQuery); // handles &amp;amp;

    const urlParams = new URLSearchParams(rawQuery);

    const currentPage = parseInt(urlParams.get('page')) || 1;
    const keyword = urlParams.get('keyword') || '';

    /* ===============================
       2. SET VALUES
    =============================== */
    if (form.page) form.page.value = currentPage;
    if (form.keyword) form.keyword.value = keyword;

    /* ===============================
       3. RESTORE OTHER FIELDS
    =============================== */
    restoreFormFromUrl(urlParams);

    /* ===============================
       4. PAGINATION
    =============================== */
    setActivePagination(currentPage);

    /* ===============================
       5. AUTO SEARCH
    =============================== */
    setTimeout(() => {


        const params = new URLSearchParams(window.location.search);
        if (params.size > 0) {
            const cleanUrl = window.location.pathname; // same page, no query string
            window.history.replaceState({}, '', cleanUrl);
        }

        searchRecords(form, currentPage);
    }, 50);

    /* ===============================
       FUNCTIONS
    =============================== */

    function restoreFormFromUrl(params) {
        params.forEach((value, key) => {
            const $el = $(
                `#frmRecordSearch [name="${key}"], 
                 #frmRecordSearchPaging [name="${key}"]`
            );
            if ($el.length) {
                $el.val(value);
            }
        });
    }

    function setActivePagination(page) {
        const pagination = document.querySelector('.pagination');
        if (!pagination) return;

        pagination.querySelectorAll('li').forEach(li => li.classList.remove('selected'));

        pagination.querySelectorAll('a').forEach(a => {
            const onclickAttr = a.getAttribute('onclick') || '';
            if (onclickAttr.includes(`goToSearchPage(${page})`)) {
                a.closest('li')?.classList.add('selected');
            }
        });
    }

    function decodeHtmlParams(query) {
        const txt = document.createElement('textarea');
        txt.innerHTML = query;
        return txt.value;
    }

});



document.addEventListener("DOMContentLoaded", function () {

    const form = document.forms.frmRecordSearch;

    if (!form) return;

    //  Reset page on Search button click
    document.querySelectorAll('.submitBtnJs').forEach(btn => {
        btn.addEventListener('click', function () {
            if (form.page) {
                form.page.value = 0; // or 1 if your system uses 1-based pages
            }
        });
    });

});



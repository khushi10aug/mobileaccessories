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

    function getCurrentPage() {
        const params = new URLSearchParams(window.location.search);
        return parseInt(params.get('page')) || 1;
    }

    function setPageInForm(page) {
        if (pagingForm && pagingForm.page) pagingForm.page.value = page;
        if (form && form.page) form.page.value = page;
    }

    function setActivePagination(page) {
        const pagination = document.querySelector('.pagination');
        if (!pagination) return;
    
        // Only remove "selected" if not page 1
        if (page > 1) {
            pagination.querySelectorAll('li').forEach(li => li.classList.remove('selected'));
        }
    
        pagination.querySelectorAll('a').forEach(a => {
            const onclickAttr = a.getAttribute('onclick') || '';
            if (onclickAttr.includes(`goToSearchPage(${page})`)) {
                const li = a.closest('li');
                if (li) li.classList.add('selected');
            }
        });
    }

    const currentPage = getCurrentPage();
    setPageInForm(currentPage);
    restoreFormFromUrl();
    setActivePagination(currentPage);

    // ✅ Remove query string from URL (without reload)
    setTimeout(function () {
    const params = new URLSearchParams(window.location.search);
    if (params.size > 0) {
        const cleanUrl = window.location.pathname; // same page, no query string
        window.history.replaceState({}, '', cleanUrl);
       searchRecords(form, currentPage);
    }
}, 100);

    function restoreFormFromUrl() {
        let rawQuery = window.location.search.replace(/&amp;/g, '&');
        const params = new URLSearchParams(rawQuery);
        if (!params.size) return;

        params.forEach((value, key) => {
            const $el = $(`#frmRecordSearch [name="${key}"], #frmRecordSearchPaging [name="${key}"]`);
            if (!$el.length) return;

            $el.val(value);
        });
    }

});


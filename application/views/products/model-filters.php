<?php
$modelCheckedArr = (isset($modelCheckedArr) && !empty($modelCheckedArr)) ? $modelCheckedArr : array();

$charArr = array();
$firstCharacter = '';
$cbrandHtml = '';
$mySelection = array();
$count = 0;
$rangeArr = [];
foreach ($modelArr as $cbrand) {
    if (in_array($cbrand['product_id'], $modelCheckedArr)) {
        $mySelection[$cbrand['product_id']] = $cbrand;
        continue;
    }
    //$totalProducts = array_key_exists('totalProducts', $brand) ? $brand['totalProducts'] : 0;

    $str = mb_substr(strtolower($cbrand['product_model']), 0, 1);
    if (is_numeric($str)) {
        $str = '0-9';
    }
    $closingTag = '';
    if ($str != $firstCharacter) {
        $cbrandHtml .= '<li><ul><li class="filter-directory_list_title ' . $str . '" data-item="' . $str . '" id="' . $str . '">' . $str . '</li>';
        $firstCharacter = $str;
        $closingTag = '</ul></li>';
    }


    $charArr[$str] =   $rangeArr[] = strtoupper($str);
    $cbrandHtml .= ' <li class="brandList-js b-' . $str . '" data-caption=' . mb_substr(strtolower($cbrand['product_model']), 0, 1) . '>
                <label class="checkbox cbrand" ><input name="model" value="' . $cbrand['product_id'] . '" data-id="model_' . $cbrand['product_id'] . '" data-title="' . $cbrand['product_model'] . '" type="checkbox" ><span class="lb-txt">' . $cbrand['product_model'] . '</span></label>
            </li>';
    $cbrandHtml .=    $closingTag;
}

?>
<div class="modal-header">
    <h5 class="modal-title"><?php echo Labels::getLabel('LBL_All_Models', $siteLangId); ?></h5>
</div>
<div class="modal-body">
    <div class="filter-directory">
        <div class="filter-directory_bar">
            <input type="text" placeholder="<?php echo Labels::getLabel('LBL_SEARCH_Model'); ?>" class="form-control filter-directory_search_input omni-search" onKeyup="autoKeywordSearch(this.value)">
            <ul class="filter-directory_indices bfilter-js">
                <?php
                $rangeArr = array_unique($rangeArr);
                foreach ($rangeArr as $char) {
                    $disabled = '';
                    if (!in_array($char, $charArr)) {
                        $disabled = 'class="filter-directory_disabled"';
                    }
                ?>
                    <li data-item="<?php echo $char; ?>" <?php echo $disabled; ?>><a href="#<?php echo $char; ?>"><?php echo $char; ?></a>
                    <?php }   ?>
            </ul>
        </div>
        <div>
            <ul class="filter-directory_list">
                <?php foreach ($mySelection as $brand) {
                    //$totalProducts = array_key_exists('totalProducts', $brand) ? $brand['totalProducts'] : 0;
                    echo ' <li>
                  <label class="checkbox model" ><input name="model" value="' . $brand['product_id'] . '" data-id="model_' . $brand['product_id'] . '" type="checkbox" checked="true" data-title="' . $brand['product_model'] . '"><span class="lb-txt">' . $brand['product_model'] . '</span></label>
              </li>';
                    /* echo ' <li>
              <label class="checkbox brand" ><input name="brands" value="' . $brand['brand_id'] . '" data-id="brand_' . $brand['brand_id'] . '" type="checkbox" checked="true" data-title="' . $brand['brand_name'] . '">' . $brand['brand_name'] . ' <span class="filter-directory_count">(' . $totalProducts . ')</span> </label>
          </li>'; */
                } ?>
                <?php echo $cbrandHtml; ?>
            </ul>
        </div>
    </div>
</div>
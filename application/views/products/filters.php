<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$catCodeArr = array();

if (isset($prodcat_code)) {
    $currentCategoryCode = substr($prodcat_code, 0, -1);
    $catCodeArr = explode("_", $currentCategoryCode);
    array_walk($catCodeArr, function (&$n) {
        $n = FatUtility::int($n);
    });
}

if (isset($priceArr) && $priceArr && 1 > FatApp::getConfig('CONF_HIDE_PRICES', FatUtility::VAR_INT, 0)) {
    if ($filterDefaultMinValue >= $filterDefaultMaxValue) {
        $filterDefaultMinValue = $filterDefaultMinValue - 1;
    }
    if ($priceArr['minPrice'] >= $priceArr['maxPrice']) {
        $priceArr['minPrice'] = $priceArr['minPrice'] - 1;
    }
?>
    <div class="sidebar-widget">
        <div class="sidebar-widget_head" data-bs-toggle="collapse" data-bs-target="#price" aria-expanded="true">
            <?php echo Labels::getLabel('LBL_Price', $siteLangId) . ' (' . (CommonHelper::getCurrencySymbolRight() ? CommonHelper::getCurrencySymbolRight() : CommonHelper::getCurrencySymbolLeft()) . ')'; ?>
        </div>
        <div class="sidebar-widget_body collapse show" id="price">
            <div class="filter-content toggle-target">
                <div class="prices" id="perform_price">
                    <div class="rangeSlider"></div>
                </div>
                <div class="clear"></div>
                <div class="slide__fields">
                    <?php $symbol = CommonHelper::getCurrencySymbolRight() ? CommonHelper::getCurrencySymbolRight() : CommonHelper::getCurrencySymbolLeft(); ?>
                    <div class="price-input">
                        <div class="price-text-box input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><?php echo $symbol; ?></span></div>
                            <input class="input-filter form-control" value="<?php echo floor($priceArr['minPrice']); ?>" data-defaultvalue="<?php echo floor($filterDefaultMinValue); ?>" name="priceFilterMinValue" type="text" id="priceFilterMinValue">

                        </div>
                    </div>
                    <span class="dash"></span>
                    <div class="price-input">
                        <div class="price-text-box input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><?php echo $symbol; ?></span></div>
                            <input class="input-filter form-control" value="<?php echo ceil($priceArr['maxPrice']); ?>" data-defaultvalue="<?php echo ceil($filterDefaultMaxValue); ?>" name="priceFilterMaxValue" type="text" id="priceFilterMaxValue">

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
<?php
} ?>

<?php if (isset($brandsArr) && count($brandsArr) > 1) {
    $brandsCheckedArr = (isset($brandsCheckedArr) && !empty($brandsCheckedArr)) ? $brandsCheckedArr : array(); ?>

    <div class="sidebar-widget">
        <div class="sidebar-widget_head" data-bs-toggle="collapse" data-bs-target="#brand" aria-expanded="true">
            <?php echo Labels::getLabel('LBL_Brand', $siteLangId); ?></div>
        <div class="sidebar-widget_body collapse show" id="brand">
            <div class="scrollbar-filters scroll scroll-y" id="scrollbar-filters">
                <ul class="list-vertical brandFilter-js">
                    <?php foreach ($brandsArr as $brand) {
                        if ($brand['brand_id'] == null) {
                            continue;
                        } ?>
                        <li><label class="checkbox brand" id="brand_<?php echo $brand['brand_id']; ?>"><input name="brands" data-id="brand_<?php echo $brand['brand_id']; ?>" value="<?php echo $brand['brand_id']; ?>" data-title="<?php echo $brand['brand_name']; ?>" type="checkbox" <?php if (in_array($brand['brand_id'], $brandsCheckedArr)) {
                                                                                                                                                                                                                                                                                                echo "checked='true'";
                                                                                                                                                                                                                                                                                            } ?>><span class="lb-txt"><?php echo $brand['brand_name']; ?></span> </label>
                        </li>
                    <?php
                    } ?>
                </ul>
            </div>


            <?php if (count($brandsArr) >= 10) { ?>
                <div class="view-all">
                    <button type="button" onClick="brandFilters()" class="link-underline">
                        <?php echo Labels::getLabel('LBL_View_More', $siteLangId); ?> </button>
                </div>
            <?php } ?>
        </div>
    </div>
    </div>
<?php
} ?>


<?php if (isset($cbrandsArr) && count($cbrandsArr) > 1) {
    
    $cbrandsCheckedArr = (isset($cbrandsCheckedArr) && !empty($cbrandsCheckedArr)) ? $cbrandsCheckedArr : array(); 
        
    ?>

    <div class="sidebar-widget">
        <div class="sidebar-widget_head" data-bs-toggle="collapse" data-bs-target="#cbrand" aria-expanded="true">
            <?php echo Labels::getLabel('LBL_Compatible_Brand', $siteLangId); ?></div>
        <div class="sidebar-widget_body collapse show" id="cbrand">
            <div class="scrollbar-filters scroll scroll-y" id="scrollbar-filters">
                <ul class="list-vertical cbrandFilter-js">
                    <?php foreach ($cbrandsArr as $cbrand) {
                        if ($cbrand['cbrand_id'] == null) {
                            continue;
                        } ?>
                        <li><label class="checkbox cbrand" id="cbrand_<?php echo $cbrand['cbrand_id']; ?>"><input name="cbrands" data-id="cbrand_<?php echo $cbrand['cbrand_id']; ?>" value="<?php echo $cbrand['cbrand_id']; ?>" data-title="<?php echo $cbrand['cbrand_name']; ?>" type="checkbox" <?php if (in_array($cbrand['cbrand_id'], $cbrandsCheckedArr)) {
                                                                                                                                                                                                                                                                                                echo "checked='true'";
                                                                                                                                                                                                                                                                                            } ?>><span class="lb-txt"><?php echo $cbrand['cbrand_name']; ?></span> </label>
                        </li>
                    <?php
                    } ?>
                </ul>
            </div>


            <?php if (count($cbrandsArr) >= 10) { ?>
                <div class="view-all">
                    <button type="button" onClick="cbrandFilters()" class="link-underline">
                        <?php echo Labels::getLabel('LBL_View_More', $siteLangId); ?> </button>
                </div>
            <?php } ?>
        </div>
    </div>
    </div>
<?php
} ?>




<?php
$optionIds = array();
$optionValueCheckedArr = (isset($optionValueCheckedArr) && !empty($optionValueCheckedArr)) ? $optionValueCheckedArr : array();

if (isset($options) && $options) { ?>
    <?php function sortByOrder($a, $b)
    {
        return $a['option_id'] - $b['option_id'];
    }

    usort($options, 'sortByOrder');
    $optionName = '';
    $liData = '';

    foreach ($options as $optionRow) {
        if ($optionName != $optionRow['option_name']) {
            if ($optionName != '') {
                echo "</div></ul></div></div> </div>";
            }
            $optionName = ($optionRow['option_name']) ? $optionRow['option_name'] : $optionRow['option_identifier']; ?>

            <div class="sidebar-widget">
                <div class="sidebar-widget_head" data-bs-toggle="collapse"
                    data-bs-target="#option<?php echo $optionRow['option_id']; ?>" aria-expanded="true">
                    <?php echo ($optionRow['option_name']) ? $optionRow['option_name'] : $optionRow['option_identifier']; ?>
                </div>
                <div class="collapse show" id="option<?php echo $optionRow['option_id']; ?>">
                    <div class="sidebar-widget_body">
                        <div class="scrollbar-filters scroll scroll-y">

                            <ul class="list-vertical">
                            <?php
                        }
                        $optionValueId = $optionRow['option_id'] . '_' . $optionRow['optionvalue_id'];
                        //$liData.= "<li>".$optionRow['optionvalue_name']."</li>";
                            ?>
                            <li>
                                <label class="checkbox optionvalue"
                                    id="optionvalue_<?php echo $optionRow['optionvalue_id']; ?>"><input name="optionvalues"
                                        value="<?php echo $optionValueId; ?>" type="checkbox"
                                        <?php if (in_array($optionRow['optionvalue_id'], $optionValueCheckedArr)) {
                                            echo "checked='true'";
                                        } ?>>
                                    <?php if ($optionRow['option_is_color']  == 1) { ?>
                                        <span class="color-dot"
                                            style="background-color: <?php echo $optionRow['optionvalue_color_code']; ?>;">
                                        </span>
                                    <?php  }  ?>
                                    <?php echo ($optionRow['optionvalue_name']) ? $optionRow['optionvalue_name'] : $optionRow['optionvalue_identifier']; ?>
                                </label>
                            </li>

                    <?php
                }
                echo "</div></ul></div> </div></div>";
            } ?>




                    <?php if (isset($conditionsArr) && count($conditionsArr) > 1) {
                        $conditionsCheckedArr = (isset($conditionsCheckedArr) && !empty($conditionsCheckedArr)) ? $conditionsCheckedArr : array(); ?>

                        <div class="sidebar-widget">
                            <div class="sidebar-widget_head" data-bs-toggle="collapse" data-bs-target="#condition"
                                aria-expanded="true">
                                <?php echo Labels::getLabel('LBL_Condition', $siteLangId); ?></div>
                            <div class="collapse show" id="condition">
                                <div class="sidebar-widget_body">
                                    <ul class="list-vertical">
                                        <?php foreach ($conditionsArr as $condition) {
                                            if (empty($condition) || $condition['selprod_condition'] == 0) {
                                                continue;
                                            } ?>
                                            <li>
                                                <label class="checkbox condition"
                                                    id="condition_<?php echo $condition['selprod_condition']; ?>">
                                                    <input value="<?php echo $condition['selprod_condition']; ?>"
                                                        data-id="condition_<?php echo $condition['selprod_condition']; ?>"
                                                        name="conditions" type="checkbox"
                                                        <?php if (in_array($condition['selprod_condition'], $conditionsCheckedArr)) {
                                                            echo "checked='true'";
                                                        } ?>>
                                                    <?php echo Product::getConditionArr($siteLangId)[$condition['selprod_condition']]; ?>
                                                </label>
                                            </li>
                                        <?php
                                        } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php
                    } ?>
                    <?php
                    if (isset($availabilityArr) && count($availabilityArr) > 1) {
                        $availability = isset($availability) ? $availability : 0; ?>

                        <div class="sidebar-widget">
                            <div class="sidebar-widget__head  collapsed" data-bs-toggle="collapse"
                                data-bs-target="#availability" aria-expanded="true">
                                <?php echo Labels::getLabel('LBL_Availability', $siteLangId); ?>
                            </div>
                            <div class="collapse show" id="availability">
                                <div class="sidebar-widget_body">
                                    <div class="toggle-target">
                                        <ul class="listing--vertical listing--vertical-chcek">
                                            <li><label class="checkbox availability" id="availability_1"><input
                                                        name="out_of_stock" value="1" type="checkbox"
                                                        <?php if ($availability == 1) {
                                                            echo "checked='true'";
                                                        } ?>><?php echo Labels::getLabel('LBL_Exclude_out_of_stock', $siteLangId); ?>
                                                </label></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                    } ?>

                        </div>

                        <script>
                            var catCodeArr = <?php echo json_encode($catCodeArr); ?>;
                            $.each(catCodeArr, function(key, value) {
                                if ($("ul li a[data-id='" + value + "']").parent().find('span')) {
                                    $("ul li a[data-id='" + value + "']").parent().find('span:first').addClass(
                                        'is-active');
                                    $("ul li a[data-id='" + value + "']").parent().find('ul:first').css('display',
                                        'block');
                                }

                            });

                            $("document").ready(function() {
                                var min = 0;
                                var max = 0;
                                <?php if (isset($priceArr) && $priceArr) { ?>
                                    var $from = $('input[name="priceFilterMinValue"]');
                                    var $to = $('input[name="priceFilterMaxValue"]');
                                    var range,
                                        min = Math.floor(<?php echo $filterDefaultMinValue; ?>),
                                        max = Math.ceil(<?php echo $filterDefaultMaxValue; ?>),
                                        from,
                                        to;

                                    const len = 4;
                                    var step = (max - min) / (len - 1);
                                    var steps = Array(len).fill().map((_, idx) => min + (idx * step));
                                    $('.rangeSlider').each(function() {
                                        var rangeSlider = $(this).get(0);
                                        noUiSlider.create(rangeSlider, {
                                            start: [$from.val(), $to.val()],
                                            step: Math.floor(step / len),
                                            range: {
                                                'min': [min],
                                                'max': [max]
                                            },
                                            connect: true,
                                            tooltips: true,
                                            direction: '<?php echo CommonHelper::getLayoutDirection(); ?>',
                                            pips: {
                                                mode: 'values',
                                                values: steps,
                                                density: 4
                                            }
                                        });

                                        rangeSlider.noUiSlider.on('change', function(values, handle) {
                                            var value = values[handle];
                                            /* handle return 0,1(min hanle and max handle) in RTL it return opposite */
                                            if (handle) {
                                                to = value;
                                            } else {
                                                from = value;
                                            }
                                            updateValues();
                                            addPricefilter(true);
                                        });

                                        var updateRange = function() {
                                            rangeSlider.noUiSlider.set([from, to]);
                                            updateValues();
                                        };

                                        $from.on("change", function() {
                                            from = $(this).prop("value");
                                            if (!$.isNumeric(from)) {
                                                from = 0;
                                            }
                                            if (from < min) {
                                                from = min;
                                            }
                                            if (from >= max) {
                                                from = (max - 1);
                                            }
                                            updateRange();
                                        });

                                        $to.on("change", function() {
                                            to = $(this).prop("value");
                                            if (!$.isNumeric(to)) {
                                                to = 0;
                                            }
                                            if (to > max) {
                                                to = max;
                                            }
                                            if (to < min) {
                                                to = min;
                                            }
                                            updateRange();
                                        });

                                        var updateValues = function() {
                                            $from.prop("value", from);
                                            $to.prop("value", to);
                                        };
                                    });

                                <?php } ?>

                                /* left side filters expand-collapse functionality [ */
                                $('.span--expand').bind('click', function() {
                                    $(this).parent('li.level').toggleClass('is-active');
                                    $(this).toggleClass('is-active');
                                    $(this).next('ul').toggle("");
                                });
                                $('.span--expand').click();
                                /* ] */

                                updatePriceFilter(<?php echo floor($priceArr['minPrice']); ?>,
                                    <?php echo ceil($priceArr['maxPrice']); ?>);
                            });

                            $("#accordian li span.acc-trigger").on('click', function() {
                                var link = $(this);
                                var closest_ul = link.siblings("ul");

                                if (link.hasClass("is-active")) {
                                    closest_ul.slideUp();
                                    link.removeClass("is-active");
                                } else {
                                    closest_ul.slideDown();
                                    link.addClass("is-active");
                                }
                            });
                            $('.dropdown-menu').on('click', function(e) {
                                e.stopPropagation();
                            });
                        </script>
<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if (!isset($siteLangId)) {
    $siteLangId = CommonHelper::getLangId();
}

$allCategories = ProductCategory::getProdCatParentChildWiseArr(
    $siteLangId,
    0,
    true,
    false,
    false,
    false,
    true,
    true
);
$allCategories = ProductCategory::pruneEmptyParentCategories($allCategories);
$sidebarCategories = [];
if (!empty($allCategories)) {
    foreach ($allCategories as $category) {
        $sidebarCategories[] = [
            'id' => $category['prodcat_id'],
            'name' => $category['prodcat_name'],
            'url' => UrlHelper::generateUrl('category', 'view', [$category['prodcat_id']]),
            'icon' => $category['icon'] ?? '',
            'children' => $category['children'] ?? [],
        ];
    }
}

$hasSlides = isset($slides) && count($slides);
if (!$hasSlides && empty($sidebarCategories)) {
    return;
}
?>

<section class="wholesale-hero-block section" data-section="wholesale-hero">
    <div class="container">
        <div class="wholesale-hero-block__row">
            <?php if (!empty($sidebarCategories)) { ?>
            <aside class="wholesale-hero-categories" aria-label="<?php echo Labels::getLabel('LBL_Categories', $siteLangId); ?>">
                <div class="wholesale-hero-categories__head">
                    <?php echo Labels::getLabel('NAV_ALL_CATEGORIES', $siteLangId); ?>
                </div>
                <ul class="wholesale-hero-categories__list">
                    <?php foreach ($sidebarCategories as $cat) { ?>
                    <li class="wholesale-hero-categories__item<?php echo !empty($cat['children']) ? ' has-children' : ''; ?>">
                        <a class="wholesale-hero-categories__link" href="<?php echo $cat['url']; ?>">
                            <span class="wholesale-hero-categories__thumb">
                                <img src="<?php echo $cat['icon']; ?>" alt="" width="32" height="32" loading="lazy">
                            </span>
                            <span class="wholesale-hero-categories__name"><?php echo $cat['name']; ?></span>
                            <?php if (!empty($cat['children'])) { ?>
                            <span class="wholesale-hero-categories__chevron" aria-hidden="true">›</span>
                            <?php } ?>
                        </a>
                        <?php if (!empty($cat['children'])) { ?>
                        <?php
                        $childCategories = $cat['children'];
                        $childCount = count($childCategories);
                        $columnCount = ($childCount > 10) ? 3 : 2;
                        $columnCount = min($columnCount, max(1, $childCount));
                        $itemsPerColumn = max(1, (int)ceil($childCount / $columnCount));
                        $childColumns = array_chunk($childCategories, $itemsPerColumn);
                        ?>
                        <div class="wholesale-hero-categories__submenu" aria-label="<?php echo $cat['name']; ?>">
                            <div class="wholesale-hero-categories__submenu-grid cols-<?php echo $columnCount; ?>">
                                <?php foreach ($childColumns as $column) { ?>
                                <div class="wholesale-hero-categories__submenu-col">
                                    <?php foreach ($column as $child) { ?>
                                    <div class="wholesale-hero-categories__submenu-group">
                                        <a class="wholesale-hero-categories__submenu-title" href="<?php echo UrlHelper::generateUrl('category', 'view', [$child['prodcat_id']]); ?>">
                                            <?php echo $child['prodcat_name']; ?>
                                        </a>
                                        <?php if (!empty($child['children'])) { ?>
                                        <ul class="wholesale-hero-categories__submenu-list">
                                            <?php foreach ($child['children'] as $grandChild) { ?>
                                            <li>
                                                <a href="<?php echo UrlHelper::generateUrl('category', 'view', [$grandChild['prodcat_id']]); ?>">
                                                    <?php echo $grandChild['prodcat_name']; ?>
                                                </a>
                                            </li>
                                            <?php } ?>
                                        </ul>
                                        <?php } ?>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </li>
                    <?php } ?>
                </ul>
            </aside>
            <?php } ?>

            <div class="wholesale-hero-block__slider">
                <?php
                if ($hasSlides) {
                    $embeddedInWholesaleHero = true;
                    $this->includeTemplate('_partial/homePageSlides.php', [
                        'slides' => $slides,
                        'siteLangId' => $siteLangId,
                        'fullWidth' => $fullWidth ?? 1,
                        'embeddedInWholesaleHero' => true,
                    ]);
                }
                ?>
            </div>

            <?php $this->includeTemplate('_partial/wholesale/home-wholesale-services-panel.php', ['siteLangId' => $siteLangId]); ?>
        </div>
    </div>
</section>

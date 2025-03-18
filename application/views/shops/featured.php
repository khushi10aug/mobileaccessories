<?php defined('SYSTEM_INIT') or die('Invalid Usage');
?>
<div id="body" class="body">
    <?php $this->includeTemplate('_partial/page-head-section.php', ['headLabel' => Labels::getLabel('LBL_FEATURED_SHOPS')]); ?>
        <section class="section" data-section="section">
            <div class="container">
                <div id="listing"> </div>
                <div id="loadMoreBtnDiv"></div>
            </div>
        </section>
</div>
<?php echo $searchForm->getFormHtml(); ?>
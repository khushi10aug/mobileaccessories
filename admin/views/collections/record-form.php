<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('data-onclear', 'recordForm(' . $recordId . ',' . $collection_type . ')');

$fld = $frm->getField('collection_records[]');
$fld->setFieldTagAttribute('id', 'collectionItemJs');
$fld->addFieldTagAttribute('multiple', 'multiple');
$fld->addFieldTagAttribute('data-allow-clear', 'false');

$actionName = 'autocomplete';
$hideSelectField = 'hide-addrecord-field--js';
switch ($collection_type) {
    case Collections::COLLECTION_TYPE_PRODUCT:
        $controllerName = 'SellerProducts';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_CATEGORY:
        $controllerName = 'ProductCategories';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_SHOP:
        $controllerName = 'Shops';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_BRAND:
        $controllerName = 'Brands';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_BLOG:
        $controllerName = 'BlogPosts';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_FAQ:
        $controllerName = 'Faq';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_FAQ_CATEGORY:
        $controllerName = 'FaqCategories';
        $hideSelectField = '';
        break;
    case Collections::COLLECTION_TYPE_TESTIMONIAL:
        $controllerName = 'Testimonials';
        $hideSelectField = '';
        break;
    default:
        $controllerName = '';
        $actionName = '';
        break;
}

$generalTab['attr']['onclick'] = 'collectionForm(' . $collection_type . ', ' . $collection_layout_type . ', ' . $recordId . ');';
$activeGentab = false;

if (!in_array($collection_type, Collections::COLLECTION_WITHOUT_RECORDS)) {
    $otherButtons[] = [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'recordForm(' . $recordId . ',' . $collection_type . ')',
            'title' => Labels::getLabel('LBL_LINK_RECORDS', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_LINK_RECORDS', $siteLangId),
        'isActive' => true
    ];
}

if (!in_array($collection_type, Collections::COLLECTION_WITHOUT_MEDIA) && !in_array($collection_layout_type, Collections::COLLECTIONS_FOR_WEB_ONLY) || in_array($collection_layout_type, Collections::COLLECTION_WITH_MEDIA)) {
    $otherButtons[] = [
        'attr' => [
            'href' => 'javascript:void(0)',
            'onclick' => 'collectionMediaForm(' . $recordId . ',' . $collection_type . ')',
            'title' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        ],
        'label' => Labels::getLabel('LBL_MEDIA', $siteLangId),
        'isActive' => false
    ];
}

$includeTabs = ($collection_layout_type != Collections::TYPE_PENDING_REVIEWS1);

require_once(CONF_THEME_PATH . '_partial/listing/form.php');

$str = Labels::getLabel('ERR_YOU_CANNOT_BIND_MORE_THAN_ALLOWED_LIMIT_{LIMIT}', $siteLangId);
$errorMsg = CommonHelper::replaceStringData($str, ['{LIMIT}' => Collections::LIMIT_COLLECTION_RECORDS]);
?>

<script type="text/javascript">
    var ctrlName = '<?php echo $controllerName; ?>';
    var actionName = '<?php echo $actionName; ?>';
    var recordId = '<?php echo $recordId; ?>';
    var recordLimit = <?php echo Collections::LIMIT_COLLECTION_RECORDS ?>;
    
    $(function() {
        select2('collectionItemJs', fcom.makeUrl(ctrlName, actionName), function(obj) {
            return {
                excludeRecords: obj.val()
            }
        }, function(e) {
            e.preventDefault();
            if (recordLimit <= $(e.currentTarget).val().length) {
                fcom.displayErrorMessage('<?php echo $errorMsg; ?>');
                return;
            }
            updateRecord(e, recordId);
        }, function(e) {
            if (!confirm(langLbl.confirmRemoveProduct)) {
                e.preventDefault();
                return false;
            }
            var item = e.params.args.data;          
            let data = $('#collectionItemJs').select2('data');
            data = data.filter((option) => option.id!= item.id).map(option => option.id);    
            $('#collectionItemJs').val(data).trigger('change');                      
            removeCollectionRecord(recordId, item.id);
        });
    });
</script>
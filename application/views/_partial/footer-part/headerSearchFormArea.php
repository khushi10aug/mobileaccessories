<?php defined('SYSTEM_INIT') or die('Invalid Usage');
$openSerachForm = $openSerachForm ?? false;
$headerSrchFrm->getFormTag();

$keywordFld = $headerSrchFrm->getField('keyword');
$keywordFld->overrideFldType('search');
$keywordFld->setFieldTagAttribute('placeholder', Labels::getLabel('LBL_I_am_looking_for...', $siteLangId));

$keywordFld->setFieldTagAttribute('id', 'header_search_keyword');

$selectFld = $headerSrchFrm->getField('category');
$selectFld->setFieldTagAttribute('id', 'searched_category');
?>

<?php if ($openSerachForm) {
    $headerSrchFrm->setFormTagAttribute('class', ' open-search-form');
    $keywordFld->setFieldTagAttribute('class', 'open-search-input search--keyword search--keyword--js');
?>
    <div class="open-search">
        <?php echo $headerSrchFrm->getFormTag(); ?>
        <?php /* <select class="open-search-select" name="" id="">
                <option value="">Select</option>
            </select>  */ ?>
        <?php echo $headerSrchFrm->getFieldHTML('keyword'); ?>
        <button class="open-search-btn">
            <svg class="svg" width="18" height="18" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24" fill="currentColor">
                <path
                    d="M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z">
                </path>
            </svg></button>
        <?php echo $headerSrchFrm->getFieldHTML('category'); ?>
        </form>
        <div id="search-suggestions-js" class="search-suggestions-js"> </div>
        <?php echo $headerSrchFrm->getExternalJS(); ?>
    </div>
<?php } else {
    $headerSrchFrm->setFormTagAttribute('class', ' mega-search-form');
    $keywordFld->setFieldTagAttribute('class', 'mega-search-input search--keyword search--keyword--js'); ?>
    <div class="offcanvas offcanvas-top offcanvas-mega-search" tabindex="-1" id="mega-nav-search">

        <div class="mega-search">
            <?php
            $imgDataType = '';
            $logoWidth = '';
            $fileData = AttachedFile::getAttachment(AttachedFile::FILETYPE_FRONT_LOGO, 0, 0, $siteLangId, false);
            $uploadedTime = AttachedFile::setTimeParam($fileData['afile_updated_at']);
            if (AttachedFile::FILE_ATTACHMENT_TYPE_SVG == $fileData['afile_attachment_type']) {
                $siteLogo = UrlHelper::getStaticImageUrl($fileData['afile_physical_path']) . $uploadedTime;
                $imgDataType = 'data-type="svg"';
                $logoWidth = 'width="120"';
            } else {
                $aspectRatioArr = AttachedFile::getRatioTypeArray($siteLangId);
                $siteLogo = UrlHelper::getCachedUrl(UrlHelper::generateFullFileUrl('Image', 'siteLogo', array($siteLangId), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
            }
            ?>
            <div class="logo" <?php echo $imgDataType; ?>>
                <a href="<?php echo UrlHelper::generateUrl(); ?>">
                    <img <?php if (AttachedFile::FILE_ATTACHMENT_TYPE_OTHER == $fileData['afile_attachment_type'] && $fileData['afile_aspect_ratio'] > 0) { ?>
                        data-ratio="<?php echo $aspectRatioArr[$fileData['afile_aspect_ratio']]; ?>" <?php } ?>
                        src="<?php echo $siteLogo; ?>"
                        alt="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>"
                        title="<?php echo FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '') ?>" <?php echo $logoWidth; ?> />
                </a>
            </div>
            <div class="mega-search-inner">
                <?php echo $headerSrchFrm->getFormTag(); ?>
                <?php echo $headerSrchFrm->getFieldHTML('keyword'); ?>
                <div id="search-suggestions-js"> </div>
                <?php echo $headerSrchFrm->getFieldHTML('category'); ?>
                </form>
                <?php echo $headerSrchFrm->getExternalJS(); ?>
            </div>
            <button type="button" class="btn btn-close text-reset btn-search-close" data-bs-dismiss="offcanvas" aria-label="Close">
            </button>
        </div>
    </div>
<?php } ?>
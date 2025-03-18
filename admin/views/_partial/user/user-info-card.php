<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$onclick = !empty($onclick) ? "onclick = " . $onclick : "";
$href = isset($href) && !empty($onclick) ? "javascript:void(0)" : '';
$extraClass = $extraClass ?? '';
$displayEmail = $displayEmail ?? true;
$userTitleClass = $userTitleClass ?? 'user-profile_title';
$title = $title ?? '';
$addVerifiedBadge = $addVerifiedBadge ?? false;
$displayProfileImage = $displayProfileImage ?? true;

$verifiedBadge = '';
if (true === $addVerifiedBadge) {
    $isverfied = (applicationConstants::YES == $user['credential_verified']);
    $class = $isverfied ? 'is-verified' : '';
    $verifiedTitle = $isverfied ? Labels::getLabel('LBL_VERIFIED', $siteLangId) : Labels::getLabel('LBL_NOT_VERIFIED', $siteLangId);
    $verifiedBadge = '<div class="verified ' . $class . '" data-bs-toggle="tooltip" data-bs-palcement="top" title="' . $verifiedTitle . '">
                    <svg class="svg" width="16" height="16">
                        <use
                            xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite.yokart.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#icon-verified">
                        </use>
                    </svg>
                </div>';
}

$credUserName = isset($user['credential_username']) && !empty($user['credential_username']) ? $user['credential_username'] : '';
$username = !empty($user['user_name']) ? $user['user_name'] . (!empty($credUserName) ? ' (' . $credUserName . ')' : '') : $credUserName;
$displayUsername = $displayUsername ?? true;
$extraHtml = $extraHtml ?? '';
?>
<div class="user-profile <?php echo $extraClass; ?>">
    <?php if ($displayProfileImage) {
        $uploadedTime = AttachedFile::setTimeParam($user['user_updated_on']);
        $userImageUrl = UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'user', array($user['user_id'], ImageDimension::VIEW_MINI_THUMB, true), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg');
        $getUserAspectRatio = ImageDimension::getData(ImageDimension::TYPE_USER, ImageDimension::VIEW_MINI_THUMB);

    ?>
        <a href="<?php echo UrlHelper::getCachedUrl(UrlHelper::generateFileUrl('image', 'user', array($user['user_id'], ImageDimension::VIEW_MEDIUM, true), CONF_WEBROOT_FRONT_URL) . $uploadedTime, CONF_IMG_CACHE_TIME, '.jpg'); ?>" data-featherlight="image">
            <figure class="user-profile_photo" data-ratio="<?php echo $getUserAspectRatio[ImageDimension::VIEW_MINI_THUMB]['aspectRatio']; ?>">
                <img data-aspect-ratio="<?php echo $getUserAspectRatio[ImageDimension::VIEW_MINI_THUMB]['aspectRatio']; ?>" width="<?php echo $getUserAspectRatio['width']; ?>" height="<?php echo $getUserAspectRatio['height']; ?>" title="<?php echo $user['user_name']; ?>" alt="<?php echo $user['user_name']; ?>" src="<?php echo $userImageUrl; ?>">
                <?php echo $verifiedBadge; ?>
            </figure>
        </a>
    <?php } ?>
    <div class="user-profile_data">
        <?php if ($displayUsername) { ?>
            <div class="verified-wrap">
                <?php if (!empty($href) || !empty($onclick)) { ?>
                    <a class="<?php echo $userTitleClass; ?>" href="<?php echo $href; ?>" <?php echo $onclick; ?> title="<?php echo $title; ?>" data-bs-toggle="tooltip">
                        <?php echo $username; ?>
                    </a>
                <?php } else { ?>
                    <span class="<?php echo $userTitleClass; ?>"><?php echo $username; ?> </span>
                <?php } ?>
            </div>
        <?php } ?>

        <?php if ($displayEmail) { ?>
            <span class="text-muted">
                <?php
                if (isset($emailOnClick)) {
                    echo '<a class="cell-link" href="javascript:void(0)" onclick="' . $emailOnClick . '">';
                }
                echo $user['credential_email'];
                if (isset($emailOnClick)) {
                    echo "</a>";
                }
                ?>
            </span>
        <?php } ?>
        <?php
        if (isset($user['extra_text']) || !empty($user['extra_text'])) {
            if (is_array($user['extra_text'])) {
                foreach ($user['extra_text'] as $d) {
                    $class = $d['class'] ?? '';
                    $text = $d['text'] ?? '';
                    echo '<span class="' . $class . '">' . $text . '</span>';
                }
            } else {
                $class = $user['extra_text_class'] ?? '';
                echo '<span class="' . $class . '">' . $user['extra_text'] . '</span>';
            }
        }
        echo $extraHtml;
        ?>
    </div>
</div>
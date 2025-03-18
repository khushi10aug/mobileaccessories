<?php
defined('SYSTEM_INIT') or die('Invalid Usage');

$pagination = '';

if ($pageCount < 1) {
    return $pagination;
}

/*Number of links to display*/
$linksToDisp = isset($linksToDisp) ? $linksToDisp : 2;

/* Current page number */
$pageNumber = $page;

/*arguments mixed(array/string(comma separated)) // function arguments*/
$arguments = (isset($arguments)) ? $arguments : null;

/*padArgListTo boolean(T/F) // where to pad argument list (left/right) */
$padArgToLeft = (isset($padArgToLeft)) ? $padArgToLeft : true;

/*On clicking page link which js function need to call*/
$callBackJsFunc = isset($callBackJsFunc) ? $callBackJsFunc : 'goToSearchPage';


if (null != $arguments) {
    if (is_array($arguments)) {
        $args = implode(', ', $arguments);
    } elseif (is_string($arguments)) {
        $args = $arguments;
    }
    if ($padArgToLeft) {
        $callBackJsFunc = $callBackJsFunc . '(' . $args . ', xxpagexx);';
    } else {
        $callBackJsFunc = $callBackJsFunc . '(xxpagexx, ' . $args . ');';
    }
} else {
    $callBackJsFunc = $callBackJsFunc . '(xxpagexx);';
}

$pagination .= FatUtility::getPageString(
    '<li class="pagination-item"><a href="javascript:void(0);" onclick="' . $callBackJsFunc . '">xxpagexx</a></li>',
    $pageCount,
    $pageNumber,
    ' <li class="pagination-item selected"><a href="javascript:void(0);">xxpagexx</a></li>',
    ' <li class="pagination-item dotted"><a href="javascript:void(0);">...</a></li> ',
    $linksToDisp,
    ' <li class="pagination-item rewind"><a href="javascript:void(0);" onclick="' . $callBackJsFunc . '"><svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#double-arrow-left">
    </use>
</svg></a></li>',
    ' <li class="pagination-item forward"><a href="javascript:void(0);" onclick="' . $callBackJsFunc . '"><svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#double-arrow-right">
    </use>
</svg></a></li>',
    ' <li class="pagination-item prev"><a href="javascript:void(0);" onclick="' . $callBackJsFunc . '"><svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#arrow-left">
    </use>
</svg></a></li>',
    ' <li class="pagination-item next"><a href="javascript:void(0);" onclick="' . $callBackJsFunc . '"><svg class="svg" width="20" height="20">
    <use xlink:href="' . CONF_WEBROOT_URL . 'images/retina/sprite-actions.svg' . AttachedFile::setTimeParam(RELEASE_DATE) . '#arrow-right">
    </use>
</svg></a></li>'
);

$ul = new HtmlElement(
    'ul',
    array(
        'class' => 'pagination',
    ),
    $pagination,
    true
);
echo ' ';
echo $ul->getHtml();

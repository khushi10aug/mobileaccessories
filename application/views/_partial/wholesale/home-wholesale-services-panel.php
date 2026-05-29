<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

if (!isset($siteLangId)) {
    $siteLangId = CommonHelper::getLangId();
}

$contactUrl = UrlHelper::generateUrl('custom', 'contactUs');
$faqUrl = UrlHelper::generateUrl('guest-user', 'login-form');
$sellerUrl = UrlHelper::generateUrl('supplier', 'form');
$shopUrl = UrlHelper::generateUrl('shops');

$services = [
    [
        'icon' => '📦',
        'name' => 'Wholesale',
        'desc' => 'Browse catalog with bulk-friendly pricing.',
        'url' => $shopUrl,
    ],
];

if (0 < FatApp::getConfig('CONF_RFQ_MODULE', FatUtility::VAR_INT, 0) && 0 < FatApp::getConfig('CONF_GLOBAL_RFQ_MODULE', FatUtility::VAR_INT, 0)) {
    $services[] = [
        'icon' => '💬',
        'name' => 'Tailored Service',
        'desc' => 'Request a tailored B2B quote.',
        'url' => 'javascript:void(0);',
        'onclick' => 'requestForQuoteFn(0);',
    ];
} else {
    $services[] = [
        'icon' => '💬',
        'name' => 'Bulk Enquiry',
        'desc' => 'Contact us for wholesale pricing.',
        'url' => $contactUrl,
    ];
}

$services[] = [
    'icon' => '🏪',
    'name' => 'Become a Seller',
    'desc' => 'Open your store nationwide.',
    'url' => $sellerUrl,
];
$services[] = [
    'icon' => '🚚',
    'name' => 'Bulk Shipping',
    'desc' => 'Flexible logistics for distributors.',
    'url' => $contactUrl,
];
$services[] = [
    'icon' => '✓',
    'name' => 'Buyer Support',
    'desc' => 'Help with orders and returns.',
    'url' => $faqUrl,
];
?>

<aside class="wholesale-hero-services" aria-label="Wholesale services">
    <div class="wholesale-hero-services__head">
        <h2 class="wholesale-hero-services__title">Wholesale Made Simple</h2>
        <p class="wholesale-hero-services__subtitle">One-Stop Wholesale Service</p>
    </div>
    <ul class="wholesale-hero-services__list">
        <?php foreach ($services as $service) { ?>
        <li>
            <a class="wholesale-hero-services__link"
                href="<?php echo $service['url']; ?>"
                <?php echo !empty($service['onclick']) ? 'onclick="' . $service['onclick'] . '"' : ''; ?>>
                <span class="wholesale-hero-services__icon" aria-hidden="true"><?php echo $service['icon']; ?></span>
                <span class="wholesale-hero-services__text">
                    <strong><?php echo $service['name']; ?></strong>
                    <span><?php echo $service['desc']; ?></span>
                </span>
            </a>
        </li>
        <?php } ?>
    </ul>
</aside>

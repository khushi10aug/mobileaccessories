<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Mobile Accessories",
  "url": "https://www.mobileaccessories.in",
  "logo": "https://www.mobileaccessories.in/logo.png",
  "description": "Buy the latest and best mobile accessories including covers, chargers, earphones, screen protectors, and more at MobileAccessories.in.",
  "founder": "iCare eTrade Private Limited",
  "foundingDate": "2011",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+918451066698",
    "contactType": "Customer Service",
    "areaServed": "IN",
    "availableLanguage": ["English", "Hindi"]
  },
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Heera Court, Shop2, L.J.Road,Mahim",
    "addressLocality": "Mumbai",
    "addressRegion": "Mumbai",
    "postalCode": "400016",
    "addressCountry": "IN"
  },
  "sameAs": [
    "https://www.facebook.com/mobileaccessories.in",
    "https://www.instagram.com/mobileaccessories.in"
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Mobile Accessories – Shop Mobile Covers, Chargers, Earphones & More",
  "url": "https://www.mobileaccessories.in",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://www.mobileaccessories.in/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

</head>
<?php
$bodyClass = ($controllerName == 'Home') ? 'home wholesale-theme' : 'inner';
if ($controllerName == 'Blog') {
    $bodyClass = 'is--blog';
}
if ($controllerName == 'Checkout' || $controllerName == 'SubscriptionCheckout') {
    $bodyClass = 'is-checkout';
}

if (CommonHelper::demoUrl()) {
    $bodyClass .= ' have-fixed-btn';
}
?>

<body class="<?php echo $bodyClass; ?> ">
    <script>
        <?php
        if (Message::getInfoCount() > 0 || Message::getDialogCount() > 0) { ?>
            $.ykmsg.info('<?php echo html_entity_decode(Message::getHtml()); ?>');
        <?php } else if (Message::getErrorCount() > 0) { ?>
            $.ykmsg.error('<?php echo html_entity_decode(Message::getHtml()); ?>');
        <?php } else if (Message::getMessageCount() > 0) { ?>
            $.ykmsg.success('<?php echo html_entity_decode(Message::getHtml()); ?>');
        <?php } ?>
    </script>

    <?php
    if (FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", FatUtility::VAR_STRING, '') /* && User::checkStatisticalCookiesEnabled() == true */) {
        echo FatApp::getConfig("CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT", FatUtility::VAR_STRING, '');
    }

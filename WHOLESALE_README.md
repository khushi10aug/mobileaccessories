# Wholesale Theme Implementation Guide

This guide explains how to implement the TVCMALL-inspired wholesale theme across your website.

## Files Created/Modified

### New Files Created:
1. `application/views/_partial/headerUtilityBar.php` - Black utility bar at top (Contact, FAQs, etc.)
2. `application/views/_partial/wholesale/home-wholesale-hero.php` - Wholesale services section
3. `application/views/_partial/wholesale/home-featured-categories.php` - Featured categories grid
4. `application/views/_partial/wholesale/apply-theme.php` - Helper to apply wholesale theme class
5. `application/views/css/wholesale.css` - Compiled wholesale CSS (auto-generated from SCSS)
6. `application/views/scss/layout/_wholesale.scss` - Wholesale theme styles

### Modified Files:
1. `application/views/_partial/topHeader.php` - Added utility bar, wholesale tagline, centered search
2. `application/views/_partial/desktop-nav.php` - Added wholesale navigation pills
3. `application/views/footer.php` - Added wholesale-footer class

## How to Apply Wholesale Theme

### Method 1: Apply to Entire Site

Add to your main layout file or `commonHeadTop.php`:

```php
<body class="wholesale-theme">
    <div class="wrapper wholesale-theme">
        ...
    </div>
</body>
```

### Method 2: Apply to Specific Pages

In your page template (e.g., `home/index.php`):

```php
<?php 
$this->includeTemplate('_partial/wholesale/apply-theme.php'); 
?>
```

### Method 3: Apply via Controller

In HomeController or relevant controller:

```php
$this->set('bodyClass', 'wholesale-theme');
```

## Home Page Layout Order (Like TVCMALL)

The recommended order for home page sections:

1. **Header Utility Bar** - Black bar with Contact, FAQs, Become a Seller
2. **Main Header** - Logo, Search, Cart, Account
3. **Navigation Bar** - Categories + Quick Links (New Arrivals, Best Sellers)
4. **Hero Slider** - Main promotional banners
5. **Featured Categories Grid** - 6-12 top categories with images
6. **Wholesale Services** - 5-card grid explaining your B2B services
7. **Scrolling Stats Bar** - Black bar with key selling points
8. **New Arrivals Section** - Product carousel
9. **Best Sellers Section** - Product carousel
10. **More Product Collections** - As configured in admin
11. **Newsletter Section** - (if enabled)
12. **Footer** - Dark theme with white text

## To Add Sections to Home Page

### Option 1: Modify HomeController

In `application/controllers/HomeController.php`, add after getting collections:

```php
// Add featured categories after hero
$this->set('showFeaturedCategories', true);

// Add wholesale services after categories
$this->set('showWholesaleServices', true);
```

### Option 2: Create Custom Collection Layout

In Admin Panel:
1. Go to Home Page Collections
2. Create new collection with custom layout type
3. Use the wholesale partial templates

### Option 3: Directly in home/index.php

Modify `application/views/home/index.php`:

```php
<main id="main" class="main">
    <?php 
    // Loop through collections and inject custom sections
    $heroShown = false;
    foreach ($collectionTemplates as $key => $collection) {
        echo FatUtility::decodeHtmlEntities($collection['html']);
        
        // After first hero slider, show featured categories
        if (!$heroShown && strpos($collection['html'], 'hero-slider') !== false) {
            $this->includeTemplate('_partial/wholesale/home-featured-categories.php');
            $heroShown = true;
        }
    }
    
    // Show wholesale services
    $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
    
    // Trust banners
    $this->includeTemplate('_partial/footerTrustBanners.php');
    ?>
</main>
```

## Styling Customization

### Change Brand Color

Edit `application/views/scss/layout/_wholesale.scss`:

```scss
// Override brand color for wholesale
$brand-color: #0066cc; // Your wholesale brand color
$brand-color-inverse: #ffffff;
```

### Customize Utility Bar

Edit `application/views/scss/layout/_wholesale.scss`:

```scss
.header-utility {
  background: #000; // Change to your color
  color: #fff;
  font-size: 0.75rem;
}
```

## Labels to Add

Add these labels in Admin > Labels for multi-language support:

- `LBL_WHOLESALE_TAGLINE` - "India's #1 B2B Mobile Accessories Marketplace"
- `LBL_Browse_By_Category` - "Browse By Category"
- `LBL_New_Arrivals` - "New Arrivals"
- `LBL_Best_Sellers` - "Best Sellers"
- `LBL_Bulk_Deals` - "Bulk Deals"
- `LBL_Become_A_Seller` - "Become a Seller"

## CSS Compilation

After modifying SCSS files, compile CSS:

```bash
# If you have SCSS compiler
sass application/views/scss/layout/_wholesale.scss application/views/css/wholesale.css

# Or use your build system
npm run build-css
```

## Troubleshooting

### Styles not applying?

1. Make sure `wholesale-theme` class is on `<body>` or `.wrapper`
2. Check that wholesale.css is included in your page
3. Clear browser cache and server cache
4. Verify SCSS compiled successfully

### Featured categories not showing?

1. Make sure you have categories with images in admin
2. Check that ProductCategory class is available
3. Verify AttachedFile for category thumbs

### Utility bar not showing?

1. Only shows on desktop (hidden on mobile by default)
2. Check that headerUtilityBar.php is included before top-bar
3. Verify labels are defined in admin

## Best Practices

1. **Performance**: Cache category images and collection templates
2. **Mobile**: Test mobile layout thoroughly (wholesale theme is optimized for desktop)
3. **Content**: Update wholesale services cards with your actual services
4. **Images**: Use high-quality category images (minimum 200x200px)
5. **SEO**: Add proper alt tags to all images

## Support

For questions or issues with the wholesale theme implementation:
- Check SCSS comments in `_wholesale.scss`
- Review TVCMALL reference: https://www.tvcmall.com/
- Test in multiple browsers and devices

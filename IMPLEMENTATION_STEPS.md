# Wholesale Theme - Quick Implementation Steps

## 🚀 Quick Start (5 Minutes)

### Step 1: Apply Wholesale Theme Class

Edit `application/views/_partial/header/commonHeadTop.php` and add `wholesale-theme` class to the body tag:

```php
<!-- Find the opening body tag and modify it -->
<body class="wholesale-theme">
```

OR add this line after `<div class="wrapper">`:
```php
<div class="wrapper wholesale-theme">
```

### Step 2: Compile or Link CSS

**Option A: Link Pre-compiled CSS**
Edit your main CSS include file or add to header:
```html
<link rel="stylesheet" href="<?php echo CONF_WEBROOT_URL; ?>application/views/css/wholesale.css">
```

**Option B: Compile SCSS**
If you have a build process:
```bash
sass application/views/scss/layout/_wholesale.scss:application/views/css/wholesale.css
```

### Step 3: Modify Home Page Template

**Option A: Replace Entire File**
1. Backup: `cp application/views/home/index.php application/views/home/index-backup.php`
2. Use wholesale version: `cp application/views/home/index-wholesale.php application/views/home/index.php`

**Option B: Add Sections Manually**

Edit `application/views/home/index.php`:

```php
<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<?php 
// Add this at the top
$this->includeTemplate('_partial/wholesale/apply-theme.php');
?>

<script>
ykevents.viewContent();
</script>

<main id="main" class="main">
    <?php 
    $heroShown = false;
    $categoriesShown = false;
    
    foreach ($collectionTemplates as $collection) {
        echo FatUtility::decodeHtmlEntities($collection['html']);
        
        // After hero slider, show featured categories
        if (!$categoriesShown && strpos($collection['html'], 'hero-slider') !== false) {
            $this->includeTemplate('_partial/wholesale/home-featured-categories.php');
            $this->includeTemplate('_partial/wholesale/home-wholesale-hero.php');
            $categoriesShown = true;
        }
    }
    
    $this->includeTemplate('_partial/footerTrustBanners.php');
    ?>
</main>
```

### Step 4: Add Required Labels

Go to **Admin Panel > Labels** and add:

| Label Key | English Value |
|-----------|---------------|
| `LBL_WHOLESALE_TAGLINE` | India's #1 B2B Mobile Accessories |
| `LBL_Browse_By_Category` | Browse By Category |
| `LBL_New_Arrivals` | New Arrivals |
| `LBL_Best_Sellers` | Best Sellers |
| `LBL_Bulk_Deals` | Bulk Deals |

### Step 5: Clear Cache

```php
// In admin or via code
CacheHelper::clearCache();
```

Or manually delete:
- `tmp/cache/*`
- Browser cache (Ctrl+Shift+R)

---

## 🎨 Customization

### Change Brand Color

Edit `application/views/scss/layout/_wholesale.scss`:

```scss
// At the top of the file, override these:
.wholesale-theme .main-bar {
  background: #your-brand-color;  // Replace with your color
}
```

### Customize Wholesale Services

Edit `application/views/_partial/wholesale/home-wholesale-hero.php`:

Update the service cards around line 15-48 with your actual services.

### Modify Featured Categories Count

Edit `application/views/_partial/wholesale/home-featured-categories.php`:

```php
// Line 9 - Change 12 to your desired number
if ($category['prodcat_parent'] == 0 && $count < 12) {
```

---

## 📋 Verification Checklist

After implementation, verify these elements appear correctly:

- [ ] **Black utility bar** at the very top with Contact, FAQs links
- [ ] **Wholesale tagline** next to logo (desktop only)
- [ ] **Search bar centered** in header (desktop)
- [ ] **Colored navigation bar** (brand color) with quick links
- [ ] **Hero slider** displays correctly
- [ ] **Featured categories grid** shows below hero (6-12 categories)
- [ ] **Wholesale services section** with 5 cards
- [ ] **Black scrolling stats bar** with your highlights
- [ ] **Product sections** display normally
- [ ] **Dark footer** with white text

---

## 🐛 Troubleshooting

### Styles Not Applying?

1. **Check wrapper has class:**
   ```javascript
   // Open browser console and run:
   document.querySelector('.wrapper').classList.contains('wholesale-theme')
   // Should return true
   ```

2. **Check CSS is loaded:**
   - View page source (Ctrl+U)
   - Search for "wholesale.css"
   - If not found, add to header includes

3. **Clear all caches:**
   - Server cache: `tmp/cache/`
   - Browser: Ctrl+Shift+Delete
   - CDN cache if applicable

### Featured Categories Not Showing?

1. **Verify categories exist:**
   - Go to Admin > Product Categories
   - Ensure you have root-level categories with images

2. **Check permissions:**
   - ProductCategory class loaded
   - AttachedFile class available

3. **Enable error reporting:**
   ```php
   // Add to top of page temporarily
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

### Layout Broken on Mobile?

The wholesale theme is optimized for desktop. For mobile:
- Utility bar is hidden (CSS: `display: none` on mobile)
- Search remains in top-right
- Categories collapse to normal mobile view

This is by design to match TVCMALL's approach.

---

## 📱 Mobile Considerations

While the wholesale theme is desktop-focused (like TVCMALL), these elements adapt for mobile:

**Hidden on Mobile:**
- Utility bar (Contact/FAQs links)
- Wholesale tagline
- Centered search bar
- Quick nav pills

**Adjusted on Mobile:**
- Featured categories: 2-column grid
- Wholesale services: 2-column grid
- Footer remains dark but stacks vertically

---

## 🔄 Reverting Changes

If you need to revert:

1. **Remove wholesale-theme class** from body/wrapper
2. **Restore original home/index.php:**
   ```bash
   cp application/views/home/index-backup.php application/views/home/index.php
   ```
3. **Remove or comment out CSS include:**
   ```html
   <!-- <link rel="stylesheet" href="...wholesale.css"> -->
   ```

---

## 📚 Related Files Reference

### Core Files:
- `application/views/_partial/topHeader.php` - Main header (modified)
- `application/views/_partial/desktop-nav.php` - Navigation (modified)
- `application/views/footer.php` - Footer (modified)
- `application/views/home/index.php` - Home page template

### New Wholesale Files:
- `application/views/_partial/headerUtilityBar.php` - Top utility bar
- `application/views/_partial/wholesale/home-wholesale-hero.php` - Services section
- `application/views/_partial/wholesale/home-featured-categories.php` - Categories grid
- `application/views/_partial/wholesale/apply-theme.php` - Theme helper
- `application/views/scss/layout/_wholesale.scss` - All styles

### Documentation:
- `WHOLESALE_README.md` - Detailed documentation
- `IMPLEMENTATION_STEPS.md` - This file

---

## 🎯 Next Steps

After basic implementation:

1. **Add actual content:**
   - Upload category images in Admin > Categories
   - Update wholesale services text
   - Configure hero slider banners

2. **Optimize for your brand:**
   - Change brand colors in SCSS
   - Update utility bar links
   - Modify stats bar content

3. **Test thoroughly:**
   - Desktop: Chrome, Safari, Firefox, Edge
   - Mobile: iOS Safari, Chrome Mobile
   - Tablet: iPad Safari

4. **Performance:**
   - Enable caching in admin
   - Optimize images (WebP format)
   - Minify CSS/JS

---

## 💡 Pro Tips

1. **For better wholesale feel:**
   - Add MOQ (Minimum Order Quantity) badges to products
   - Show bulk pricing tables
   - Add "Request Quote" CTAs prominently

2. **Content strategy:**
   - Highlight bulk discounts in hero slider
   - Feature new arrivals weekly
   - Showcase best-selling categories

3. **Trust signals:**
   - Add customer logos in trust section
   - Display certifications/awards
   - Show "Orders shipped" counter

---

## 📞 Support

If you encounter issues:

1. Check the `WHOLESALE_README.md` for detailed explanations
2. Review TVCMALL.com for reference design
3. Verify all files were created successfully
4. Check server error logs: `error_log` or `tmp/logs/`

---

Last Updated: <?php echo date('Y-m-d'); ?>

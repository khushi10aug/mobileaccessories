<?php

class SiteMapController extends MyAppController
{
    public function index()
    {
        $brandsArr = $this->getSitemapBrandsWithProducts();
        $categoriesArr = ProductCategory::getProdCatParentChildWiseArr($this->siteLangId, 0, true, false, true, false, true);
        $contentPages = ContentPage:: getPagesForSelectBox($this->siteLangId);
        $this->set('contentPages', $contentPages);
        $this->set('categoriesArr', $categoriesArr);
        $this->set('allBrands', $brandsArr);
        $this->_template->render();
    }
}

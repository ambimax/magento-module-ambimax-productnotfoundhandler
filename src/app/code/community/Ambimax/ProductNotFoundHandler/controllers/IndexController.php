<?php

class Ambimax_ProductNotFoundHandler_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Default index action (with 404 Not Found headers)
     * Used if default page don't configure or available
     *
     */
    public function defaultIndexAction()
    {
        $this->_findRewrite();
        return parent::defaultIndexAction();
    }

    /**
     * Render CMS 404 Not found page
     *
     * @param string $coreRoute
     */
    public function noRouteAction($coreRoute = null)
    {
        $this->_findRewrite();
        return parent::noRouteAction($coreRoute);
    }

    /**
     * Default no route page action
     * Used if no route page don't configure or available
     *
     */
    public function defaultNoRouteAction()
    {
        $this->_findRewrite();
        return parent::defaultNoRouteAction();
    }

    /**
     * Find product and redirect
     *
     * @return bool
     */
    protected function _findRewrite()
    {
        if ( !($sku = $this->_extractSkuFromRequestUrl()) ) {
            return false;
        }

        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

        if ( !$product || !$product->getId() ) {
            return false;
        }

        // If the product is a child of a parent product we rather show the parent
        if ( $parentUrl = $this->_getParentUrl($product) ) {
            $this->redirect($parentUrl);
            exit;
        }

        // Redirect to product url
        if ( $productUrl = $product->getProductUrl(true) ) {
            $this->redirect($productUrl);
            exit;
        }

        // Fire event for other modules to take a shot
        Mage::dispatchEvent('ambimax_productnotfoundhandler_missed', array(
            'sku'     => $sku,
            'product' => $product
        ));

        return false;
    }

    /**
     * Extract url from request url
     *
     * @return bool|string
     */
    protected function _extractSkuFromRequestUrl()
    {
        // Prepare url
        $urlSuffix = Mage::getStoreConfig('catalog/seo/product_url_suffix');
        $url = rtrim(Mage::app()->getRequest()->getServer('REQUEST_URI'), $urlSuffix);
        $url = substr(strrchr($url, '/'), 1);

        if ( empty($url) ) {
            return false;
        }

        // Figure out length of sku (no sku should be as long as 70 chars or even longer!)
        $skuLength = substr(strrchr($url, "-"), 1);
        if ( $skuLength < 70 && $skuLength != (int)$skuLength ) {
            return false;
        }
        $sku = substr($url, -(strlen('-' . $skuLength) + $skuLength), -(strlen('-' . $skuLength)));

        return !empty($sku) ? $sku : false;
    }

    /**
     * Fetches parent url - child must belong to one parent only!
     *
     * @return bool|string
     */
    protected function _getParentUrl(Mage_Catalog_Model_Product $product)
    {
        $resource = Mage::getSingleton('core/resource');
        $_read = $resource->getConnection('core_read');
        $superLinkTable = $resource->getTableName('catalog/product_super_link');
        $parentId = $_read->fetchOne("SELECT parent_id FROM {$superLinkTable} WHERE product_id = {$product->getId()}");
        $product = Mage::getModel('catalog/product')->load($parentId);

        if ( !$product || !$product->getId() ) {
            return false;
        }

        $url = $product->getProductUrl(true);
        return !empty($url) ? $url : false;
    }

    /**
     * Redirect to url
     *
     * @param $url
     */
    public function redirect($url)
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Location: ' . $url);
        exit;
    }
}

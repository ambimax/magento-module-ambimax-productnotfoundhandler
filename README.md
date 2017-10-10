# ambimax® ProductNotFoundHandler

When importing products the url-key is extended by a continuing number that makes it unique. 
Additionally a permanent redirect is created from the old url to the new one. Therefore the
```core_url_rewrite``` table grows rapidly and slows many Magento sites down.

To solve this problem we add the sku to the urls and make old urls forever (as long as the product with same sku exists) 
redirectable without overhead. This module reads the sku from url and redirects to the new url.

## Usage

Url must use this pattern

```
http://domain.tld/any-string-{sku}-{skuLength}/
```

Sku must not contain any characters except for #[^0-9a-z]+#i

## Composer

```
composer require ambimax/magento-module-ambimax-productnotfoundhandler
```

## Installation

1) Install like any other (modman/composer) module

2) To enable it please set Configuration > Web > Default Pages > CMS No Route Page (web/default/no_route) to ```productnotfoundhandler/index/noRoute``` (default: cms/index/noRoute)

3) Add sku and skuLength to all url-key attribute of all products
 
Example import usage:
```

    /**
     * Prepare product data on import
     *
     * @param array $productData
     * @return array
     */
    public function row(array $productData)
    {
        // ...
        $url = array($productData['amazon_titel'], $productData['sku'], strlen($productData['sku']));
        $product['url_key'] = $this->formatUrlKey(implode(' ', $url));
        
        // ...
        
        return $productData;
    }

    /**
     * Format Key for URL
     *
     * @param string $str
     * @return string
     */
    public function formatUrlKey($str)
    {
        $urlKey = preg_replace('#[^0-9a-z]+#i', '-', strtolower(Mage::helper('catalog/product_url')->format($str)));
        $urlKey = trim($urlKey, '-');

        return $urlKey;
    }
    
```

## Disclaimer

This module comes with no warranty at all.

## License

[MIT License](http://choosealicense.com/licenses/mit/)

## Author Information

 - Julian Bour, [ambimax® GmbH](https://www.ambimax.de)
 - Tobias Schifftner, [ambimax® GmbH](https://www.ambimax.de)

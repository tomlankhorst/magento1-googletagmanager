<?php

/**
 * DataLayer
 * Copyright © 2016 MagePal. All rights reserved.
 * See COPYING.txt for license details.
 */
class MagePal_GoogleTagManager_Model_DataLayer extends Mage_Core_Model_Abstract {
    
    /**
     * @var Quote|null
     */
    protected $_quote = null;
    
    /**
     * Datalayer Variables
     * @var array
     */
    protected $_variables = array();

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    
    /**
     * @var string
     */
    protected $_fullActionName;


    /**
     * @param MessageInterface $message
     * @param null $parameters
     */
    public function __construct() {

        $this->_customerSession = Mage::getSingleton('customer/session');
        
        $this->fullActionName = Mage::app()->getFrontController()->getAction() ? Mage::app()->getFrontController()->getAction()->getFullActionName() : 'Unknown';;
        
        $this->addVariable('pageType', $this->fullActionName);
        $this->addVariable('list', 'other');
      
        $this->setCustomerDataLayer();
        $this->setProductDataLayer();
        $this->setCategoryDataLayer();
        $this->setCartDataLayer();
          
    }

    /**
     * Return Data Layer Variables
     *
     * @return array
     */
    public function getVariables() {
        return $this->_variables;
    }

    /**
     * Add Variables
     * @param string $name
     * @param mix $value
     * @return MagePal\GoogleTagManager\Model\DataLayer
     */
    public function addVariable($name, $value) {

        if (!empty($name)) {
            $this->_variables[$name] = $value;
        }

        return $this;
    }

    
    /**
     * Set category Data Layer
     */
    protected function setCategoryDataLayer() {
        if($this->fullActionName === 'catalog_category_view'
           && $_category = Mage::registry('current_category')
        ) {
                $category = array();
                $category['id'] = $_category->getId();
                $category['name'] = $_category->getName();
                
                $this->addVariable('category', $category);
                
                $this->addVariable('list', 'category');
        }

        return $this;
    }
    
    
    /**
     * Set product Data Layer
     */
    protected function setProductDataLayer() {
        if($this->fullActionName === 'catalog_product_view'
           && $_product = Mage::registry('current_product')
        ) {
            $this->addVariable('list', 'detail');

            $product = array();
            $product['id'] = $_product->getId();
            $product['sku'] = $_product->getSkuGoogle();
            $product['name'] = $_product->getName();
            $this->addVariable('productPrice', (float)$_product->getFinalPrice());
	    $this->addVariable('productSku', $_product->getSkuGoogle());
            $this->addVariable('product', $product);
        }

        return $this;
    }

    /**
     * Set Customer Data Layer
     */
    protected function setCustomerDataLayer() {
        $customer = array();
        if ($this->_customerSession->isLoggedIn()) {
            $customer['isLoggedIn'] = true;
            $customer['id'] = $this->_customerSession->getCustomerId();
            $customer['groupId'] = $this->_customerSession->getCustomerGroupId();
            //$customer['groupCode'] = ;
        } else {
            $customer['isLoggedIn'] = false;
        }
        
        $this->addVariable('customer', $customer);

        return $this;
    }
    
    
    /**
     * Set cart Data Layer
     */
    protected function setCartDataLayer() {
        if($this->fullActionName === 'checkout_index_index'){
            $this->addVariable('list', 'cart');
        }
        
        $quote = $this->getQuote();
        $cart = array();

        if ($quote->getItemsCount()) {
            $cart['hasItems'] = true;
            
            // set items
            foreach($quote->getAllVisibleItems() as $item){
	
		$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
	
                $items[] = array(
                    'sku' => $product->getSkuGoogle(),
                    'name' => $product->getName(),
                    'price' => (float)$product->getFinalPrice(),
                    'quantity' => $item->getQty()
                );
            }
            
            $cart['items'] = $items;
            $cart['total'] = (float)$quote->getGrandTotal();
            $cart['itemCount'] = $quote->getItemsCount();
            
            
            //set coupon code
            $coupon = $quote->getCouponCode();
            
            $cart['hasCoupons'] = $coupon ? true : false;

            if($coupon){
                $cart['couponCode'] = $coupon;
            }
        }
        else{
           $cart['hasItems'] = false;
        }
         $this->addVariable('cart', $cart);
        
        return $this;
    }
    
    
    /**
     * Get active quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        if (null === $this->_quote) {
            $this->_quote = Mage::getSingleton('checkout/cart')->getQuote();
        }
        return $this->_quote;
    }
    
}

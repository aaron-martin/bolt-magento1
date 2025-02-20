<?php

class Bolt_Boltpay_TestHelper
{
    /**
     * @param $productId
     * @param $quantity
     * @return Mage_Checkout_Model_Cart
     * @throws Exception
     */
    public function addProduct($productId, $quantity)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->load($productId);
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getSingleton('checkout/cart');
        $param = array(
            'product' => $productId,
            'qty' => $quantity
        );
        $cart->addProduct($product, $param);
        $cart->save();

        return $cart;
    }

    /**
     * @param array $addressData
     * @return Mage_Checkout_Model_Type_Onepage
     * @throws Exception
     */
    public function addTestBillingAddress($addressData = array())
    {
        if (!count($addressData)) {
            $addressData = array(
                'firstname' => 'Luke',
                'lastname' => 'Skywalker',
                'street' => 'Sample Street 10',
                'city' => 'Los Angeles',
                'postcode' => '90014',
                'telephone' => '+1 867 345 123 5681',
                'country_id' => 'US',
                'region_id' => 12
            );
        }
        /** @var Mage_Checkout_Model_Type_Onepage $checkout */
        $checkout = Mage::getSingleton('checkout/type_onepage');
        $checkout->getQuote()->getBillingAddress()->addData($addressData);
        $checkout->getQuote()->getBillingAddress()->save();

        return $checkout;
    }

    public function addTestFlatRateShippingAddress($addressData, $paymentMethod)
    {
        $checkout = Mage::getSingleton('checkout/type_onepage');
        $shippingAddress = $checkout->getQuote()->getShippingAddress()->addData($addressData);
        Mage::app('default')->getStore()->setConfig('carriers/flatrate/active', 1);

        $shippingAddress
            ->setCollectShippingRates(true)
            ->setShippingMethod('flatrate_flatrate')
            ->collectShippingRates()
            ->setPaymentMethod($paymentMethod);
        $checkout->getQuote()->getShippingAddress()->save();
        return $checkout;
    }

    /**
     * @param $checkoutType
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function createCheckout($checkoutType)
    {
        Mage::unregister('_singleton/checkout/type_onepage');
        Mage::unregister('_singleton/checkout/cart');
        /** @var Mage_Checkout_Model_Type_Onepage $checkout */
        $checkout = Mage::getSingleton('checkout/type_onepage');
        $checkoutSession = $checkout->getCheckout();
        $checkoutSession->clear();
        $checkout->initCheckout();
        $checkout->saveCheckoutMethod($checkoutType);

        return $checkout;
    }

    public function addPaymentToQuote($method)
    {
        $checkout = Mage::getSingleton('checkout/type_onepage');
        $checkout->getQuote()->getPayment()->importData(array('method' => $method));
        $checkout->getQuote()->getPayment()->save();
        $checkout->getQuote()->collectTotals()->save();

        return $checkout;
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    public function getCheckoutQuote()
    {
        /** @var Mage_Checkout_Model_Type_Onepage $checkout */
        $checkout = Mage::getSingleton('checkout/type_onepage');

        return $checkout->getQuote();
    }

    /**
     * @return mixed
     */
    public function submitCart()
    {
        $checkoutQuote = $this->getCheckoutQuote();
        $service = Mage::getModel('sales/service_quote', $checkoutQuote);
        $service->submitAll();

        return $service->getOrder();
    }

    public function resetApp()
    {
        $_POST = array();
        $_REQUEST = array();
        $_GET = array();
        $this->app = Mage::app('default');
        $this->app->getStore()->resetConfig();
    }

    /**
     * @param $checkoutType
     * @param $jsonCart
     * @param $quote
     * @param $jsonHints
     * @return string
     */
    public function buildCartDataJs($checkoutType, $jsonCart, $quote, $jsonHints)
    {
        /* @var Bolt_Boltpay_Helper_Data $boltHelper */
        $boltHelper = Mage::helper('boltpay');
        $quote->setIsVirtual(false);

        $hintsTransformFunction = $boltHelper->getExtraConfig('hintsTransform');
        $configCallbacks = $boltHelper->getBoltCallbacks($checkoutType, $quote);

        return ("
            var \$hints_transform = $hintsTransformFunction;
            
            var get_json_cart = function() { return $jsonCart };
            var json_hints = \$hints_transform($jsonHints);
            var quote_id = '{$quote->getId()}';
            var order_completed = false;
            var do_checks = 1;

            window.BoltModal = BoltCheckout.configure(
                get_json_cart(),
                json_hints,
                $configCallbacks
        );");
    }

    /**
     * Gets the object reports that reports information about a class.
     *
     * @param mixed $class Either a string containing the name of the class to reflect, or an object.
     *
     * @return ReflectionClass  instance of the object used for inspection of the passed class
     * @throws ReflectionException if the class does not exist.
     */
    public static function getReflectedClass( $class ) {
        return new ReflectionClass( $class );
    }

    /**
     * Convenience method to call a private or protected function
     *
     * @param object|string     $objectOrClassName  The object of the function to be called.
     *                                              If the function is static, then a this should be a string of the class name.
     * @param string            $functionName       The name of the function to be invoked
     * @param array             $arguments          An indexed array of arguments to be passed to the function in the
     *                                              order that they are declared
     *
     * @return mixed    the value returned by the function
     *
     * @throws ReflectionException   if a specified object, class or method does not exist.
     */
    public static function callNonPublicFunction($objectOrClassName, $functionName, $arguments = [] ) {
        try {
            $reflectedMethod = self::getReflectedClass($objectOrClassName)->getMethod($functionName);
            $reflectedMethod->setAccessible(true);

            return $reflectedMethod->invokeArgs(is_object($objectOrClassName) ? $objectOrClassName : null, $arguments);
        } finally {
            if ( $reflectedMethod && ($reflectedMethod->isProtected() || $reflectedMethod->isPrivate()) ) {
                $reflectedMethod->setAccessible(false);
            }
        }
    }

    /**
     * Convenience method to get a private or protected property
     *
     * @param object|string $objectOrClassName  The object of the property to be retreived
     *                                          If the property is static, then a this should be a string of the class name.
     * @param string        $propertyName       The name of the property to be retrieved
     *
     * @return mixed    The value of the property
     *
     * @throws ReflectionException  if a specified object, class or property does not exist.
     */
    public static function getNonPublicProperty($objectOrClassName, $propertyName ) {
        try {
            $reflectedProperty = self::getReflectedClass($objectOrClassName)->getProperty($propertyName);
            $reflectedProperty->setAccessible(true);

            return $reflectedProperty->getValue( is_object($objectOrClassName) ? $objectOrClassName : null );

        } finally {
            if ( $reflectedProperty && ($reflectedProperty->isProtected() || $reflectedProperty->isPrivate()) ) {
                $reflectedProperty->setAccessible(false);
            }
        }
    }

    /**
     * Convenience method to set a private or protected property
     *
     * @param object|string $objectOrClassName  The object of the property to be set
     *                                          If the property is static, then a this should be a string of the class name.
     * @param string        $propertyName       The name of the property to be set
     * @param mixed         $value              The value to be set to the named property
     *
     * @throws ReflectionException  if a specified object, class or property does not exist.
     */
    public static function setNonPublicProperty($objectOrClassName, $propertyName, $value ) {
        try {
            $reflectedProperty = self::getReflectedClass($objectOrClassName)->getProperty($propertyName);
            $reflectedProperty->setAccessible(true);

            if (is_object($objectOrClassName)) {
                $reflectedProperty->setValue( $objectOrClassName, $value );
            } else {
                $reflectedProperty->setValue( $value );
            }
        } finally {
            if ( $reflectedProperty && ($reflectedProperty->isProtected() || $reflectedProperty->isPrivate()) ) {
                $reflectedProperty->setAccessible(false);
            }
        }
    }
}

<?php

/**
 * Class Bolt_Boltpay_Model_ObserverTest
 */
class Bolt_Boltpay_Model_ObserverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var int|null
     */
    private static $productId = null;

    /**
     * Generate dummy products for testing purposes
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        // Create some dummy product:
        self::$productId = Bolt_Boltpay_ProductProvider::createDummyProduct('PHPUNIT_TEST_' . 1);
    }

    /**
     * Delete dummy products after the test
     * @inheritdoc
     */
    public static function tearDownAfterClass()
    {
        Mage::getSingleton('checkout/cart')->truncate()->save();
        Bolt_Boltpay_ProductProvider::deleteDummyProduct(self::$productId);
    }

    /**
     * @inheritdoc
     */
    public function testCheckObserverClass()
    {
        $observer = Mage::getModel('boltpay/Observer');

        $this->assertInstanceOf('Bolt_Boltpay_Model_Observer', $observer);
    }

    /**
     * @inheritdoc
     */
    public function testAddMessageWhenCapture()
    {
        $incrementId = '100000001';
        $order = $this->getMockBuilder('Mage_Sales_Model_Order')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->setMethods(['getIncrementId'])
            ->getMock();

        $order->method('getIncrementId')
            ->will($this->returnValue($incrementId));

        $orderPayment = $this->getMockBuilder('Mage_Sales_Model_Order_Payment')
            ->setMethods(['getMethod', 'getOrder'])
            ->enableOriginalConstructor()
            ->getMock();

        $orderPayment
            ->method('getMethod')
            ->willReturn(Bolt_Boltpay_Model_Payment::METHOD_CODE);

        $orderPayment->method('getOrder')
            ->willReturn($order);

        Mage::dispatchEvent('sales_order_payment_capture', array('payment' => $orderPayment));

        $this->assertEquals('Magento Order ID: "'.$incrementId.'".', $orderPayment->getData('prepared_message'));
    }

    /**
     * @inheritdoc
     *
     * @param $quotePaymentId
     * @param $quote
     * @param $method
     * @return false|Mage_Core_Model_Abstract
     * @throws Varien_Exception
     */
    private function _createQuotePayment($quotePaymentId, $quote, $method)
    {
        $quotePayment = Mage::getModel('sales/quote_payment');
        $quotePayment->setMethod($method);
        $quotePayment->setId($quotePaymentId);
        $quotePayment->setQuote($quote);
        $quotePayment->save();

        return $quotePayment;
    }

    /**
     * @inheritdoc
     *
     * @param $productId
     * @param $quantity
     * @return Mage_Core_Model_Abstract
     * @throws Varien_Exception
     */
    private function _createGuestCheckout($productId, $quantity)
    {
        $product = Mage::getModel('catalog/product')->load($productId);
        $cart = Mage::getSingleton('checkout/cart');
        $param = array(
            'product' => self::$productId,
            'qty' => 4
        );
        $cart->addProduct($product, $param);
        $cart->save();

        $checkout = Mage::getSingleton('checkout/type_onepage');
        $addressData = array(
            'firstname' => 'Vagelis',
            'lastname' => 'Bakas',
            'street' => 'Sample Street 10',
            'city' => 'Somewhere',
            'postcode' => '123456',
            'telephone' => '123456',
            'country_id' => 'US',
            'region_id' => 12, // id from directory_country_region table
        );
        $checkout->initCheckout();
        $checkout->saveCheckoutMethod('guest');
        $checkout->getQuote()->getBillingAddress()->addData($addressData);

        $shippingAddress = $checkout->getQuote()->getShippingAddress()->addData($addressData);
        $shippingAddress
            ->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate')
            ->setPaymentMethod(Bolt_Boltpay_Model_Payment::METHOD_CODE);

        $checkout->getQuote()->getPayment()->importData(array('method' => Bolt_Boltpay_Model_Payment::METHOD_CODE));
        $checkout->getQuote()->collectTotals()->save();

        $checkout = Mage::getSingleton('checkout/type_onepage');

        $checkout->initCheckout();

        $quoteItem = Mage::getModel('sales/quote_item')
            ->setProduct($product)
            ->setQty($quantity)
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setWeight($product->getWeight())
            ->setPrice($product->getPrice());

        $checkout->getQuote()
            ->addItem($quoteItem);


        $checkout->getQuote()->collectTotals()->save();
        $checkout->saveCheckoutMethod('guest');
        $checkout->saveShippingMethod('flatrate_flatrate');

        return $cart;
    }
}
<?php

namespace TimeExpressParcels\TimeExpressParcels\Observer;

use TimeExpressParcels\TimeExpressParcels\Helper\Data as TimeExpressParcelsHelper;
use TimeExpressParcels\TimeExpressParcels\Model\Api as TimeExpressParcelsApi;
use Magento\Framework\Event\Observer;
use Magento\Framework\Logger\Monolog;
use Magento\Quote\Model\QuoteFactory;

class SalesOrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $helper;
    protected $api;
    protected $logger;
    protected $orderFactory;
    protected $quoteFactory;

    public function __construct(
        TimeExpressParcelsHelper $helper,
        TimeExpressParcelsApi $api,
        Monolog $logger,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        QuoteFactory $quoteFactory
    ) {
        $this->helper = $helper;
        $this->api = $api;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
    }

    public function execute(Observer $observer)
    {
        try {
            $type = $this->helper->getStoreConfig('carriers/timeexpressparcels/type');
            $order = $observer->getEvent()->getOrder();
            $quoteId = $order->getQuoteId();
            if ($order && $quoteId) {
                $quote = $this->quoteFactory->create()->load($quoteId);
                $isOrderUsedTimeExpressParcels = false;
                $shippingMethod = $order->getShippingMethod();

                $methods = $this->helper->getMethods();
                foreach ($methods as $serviceCode => $serviceName) {
                    $code = 'timeexpressparcels_'.$serviceCode;
                    if ($code == $shippingMethod) {
                        $isOrderUsedTimeExpressParcels = true;
                        break;
                    }
                }
                
                if ($isOrderUsedTimeExpressParcels) {
                    $this->helper->saveTrackingData($order, $quote);
                    if ($type) {
                        $this->api->generateTrackingNumber($order, $quote);
                        $this->helper->sendTrackingEmail($order, $quote);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('TimeExpressParcels SalesOrderPlaceAfter Exception: '.$e->getMessage());
        }
    }
}

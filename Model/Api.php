<?php
/**
 * Copyright © TIME EXPRESS PARCELS. All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 */

namespace TimeExpressParcels\TimeExpressParcels\Model;

use TimeExpressParcels\TimeExpressParcels\Helper\Data as TimeExpressParcelsHelper;
use Magento\Framework\Logger\Monolog;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\QuoteFactory;

class Api
{
    const ENQ_RATE_URL="http://timeexpress.dnsalias.com:880/Mobile/TimeServices.svc/EnqRateAgainstAgent";
    const CREATE_AWB_URL="http://timeexpress.dnsalias.com:880/Mobile/TimeServices.svc/AWBCreation";
    const USER_LOGIN_URL="http://timeexpress.dnsalias.com:880/Special/"
            . "TimeServices_Special.svc/Userauthentication";

    protected $helper;
    protected $logger;
    protected $quoteFactory;
    protected $escaper;
    protected $curl;

    public function __construct(
        TimeExpressParcelsHelper $helper,
        Monolog $logger,
        QuoteFactory $quoteFactory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->quoteFactory = $quoteFactory;
        $this->escaper = $escaper;
        $this->curl = $curl;
    }

    /**
     * @param RateRequest $request
     * @param string  $serviceCode
     * @return bool|float
     */
    public function getShippingPrice(RateRequest $request, $serviceCode)
    {
        $shippingPrice = false;

        $accounNo = $this->helper->getStoreConfig('timeexpressparcels_account_no');

        $weight = $request->getPackageWeight();
        $weight = $this->helper->convertWeight($weight);

        $dimensions = $this->helper->getDimensions($weight);
        $dimensionWeight = ($dimensions['breadth'] * $dimensions['length'] * $dimensions['height'])/5000;

        $weight = max($weight, $dimensionWeight);

        $destCountry = $request->getDestCountryId();
        $destCountry = $this->helper->destCountry($destCountry);

        if ($request->getOrigCountry()) {
            $origCountry = $request->getOrigCountry();
        } else {
            $origCountry = $this->helper->getStoreConfig(
                \Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID
            );
        }
        $origCountry = $this->helper->origCountry($origCountry);

        $totalPcs = $this->helper->getTotalPcs($request);

        try {
            $json_string = '{
                    "Breadth":"'.$dimensions['breadth'].'",
                    "Length":"'.$dimensions['length'].'",
                    "accounNo":"'.$accounNo.'",
                    "agent":"TEC",
                    "destination":"'.$destCountry.'",
                    "height":"'.$dimensions['height'].'",
                    "origin":"'.$origCountry.'",
                    "pcs":"'.$totalPcs.'",
                    "productType":"XPS",
                    "serviceType":"'.$serviceCode.'",
                    "weight":"'.$weight.'"
            }';

            $this->debug('timeexpressparcels rate request');
            $this->debug($json_string);

            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_ENCODING, '');
            $this->curl->setOption(CURLOPT_TIMEOUT, 0);
            $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            $this->curl->addHeader("Content-Type", "application/json");

            $this->curl->post(self::ENQ_RATE_URL, $json_string);
            $response = $this->curl->getBody();
            $this->debug('timeexpressparcels rate response');

            if ($response) {
                $this->debug($response);
                $response = utf8_encode($response);
                $response_json = json_decode($response, true);

                if ($response_json['code'] == 1) {
                    $shippingPrice = $response_json['Rate'];
                    $shippingPrice = $this->helper->convertPriceFromAED($shippingPrice);
                }
            } else {
                $error = __("Curl Error 2");
                $this->debug($error);
            }
        } catch (\Exception $e) {
            $this->debug('timeexpressparcels rate Exception: '.$e->getMessage());
        }

        return $shippingPrice;
    }

    public function generateTrackingNumber($order, $quote)
    {
        $shippingMethod = $order->getShippingMethod();
        $serviceCode = str_replace('timeexpressparcels_', '', $shippingMethod);

        $accounNo = $this->helper->getStoreConfig('timeexpressparcels_account_no');

        $weight = $order->getWeight();
        $weight = $this->helper->convertWeight($weight);

        $dimensions = $this->helper->getDimensions($weight);
        $dimensionWeight = ($dimensions['breadth'] * $dimensions['length'] * $dimensions['height'])/5000;

        $weight = max($weight, $dimensionWeight);

        $currency = $quote->getQuoteCurrencyCode();

        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $origCountry = $this->helper->getStoreConfig(\Magento\Sales\Model\Order\Shipment::XML_PATH_STORE_COUNTRY_ID);
        $origCountry = $this->helper->origCountry($origCountry);

        $totalPcs = (int)$quote->getItemsQty();

        $payment = $quote->getPayment();
        $payment_method = $payment->getMethod();

        $total = round($this->helper->convertPriceToAED($quote->getGrandTotal(), $currency), 2);

        $codAmount = 0;
        if ($payment_method == 'cashondelivery') {
            $codAmount = $total;
        }

        $billingStreet = $billingAddress->getStreet();
        $billingStreet1 = '';
        if (isset($billingStreet[0])) {
            $billingStreet1 = $billingStreet[0];
        }

        $billingStreet2 = '';
        if (isset($billingStreet[1])) {
            $billingStreet2 = $billingStreet[1];
        }

        $shippingStreet = $shippingAddress->getStreet();
        $shippingStreet1 = '';
        if (isset($shippingStreet[0])) {
            $shippingStreet1 = $shippingStreet[0];
        }

        $shippingStreet2 = '';
        if (isset($shippingStreet[1])) {
            $shippingStreet2 = $shippingStreet[1];
        }

        $specialInstruction = 'From Magento Website. '.$this->helper->getStoreName();

        $consigneeName = $this->escape($billingAddress->getFirstname().' '.$billingAddress->getLastname());
        $shipContPerson = $this->escape($shippingAddress->getFirstname().' '.$shippingAddress->getLastname());

        $account = $this->helper->getStoreConfigDb('timeexpressparcels_account_info');
        if (!$account) {
			
            return false;
        } else {
            try {
                $client = json_decode($account, true);
				if(!empty($client['phone'])){
					$client_phone=$client['phone'];
				}else{
					$client_phone="";
				}
				if(!empty($client['address'])){
					$client_address=$client['address'];
				}else{
					$client_address="";
				}
				if(!empty($client['name'])){
					$client_name=$client['name'];
				}else{
					$client_name="";
				}

                $json_string = '{
                    "Length":"'.$dimensions['length'].'",
                    "Width":"'.$dimensions['breadth'].'",
                    "height":"'.$dimensions['height'].'",
                    "pcs":"'.$totalPcs.'",
                    "accounNo":"'.$accounNo.'",
                    "codAmount":"'.$codAmount.'",
                    "consignee":"'.$this->escape($billingAddress->getFirstname().' '.$billingAddress->getLastname()).'",
                    "consigneeAddress1":"'.$this->escape($billingStreet1).'",
                    "consigneeAddress2":"'.$this->escape($billingStreet2).'",
                    "consigneeCity":"'.$this->escape($billingAddress->getCity()).'",
                    "consigneeCountry":"'.$billingAddress->getCountryId().'",
                    "consigneeFax":"'.$billingAddress->getFax().'",
                    "consigneeMob":"'.$billingAddress->getTelephone().'",
                    "consigneeName":"'.$consigneeName.'",
                    "consigneePhone":"'.$billingAddress->getTelephone().'",
                    "destination":"'.$shippingAddress->getCountryId().'",
                    "goodDescription":"'.$this->escape($this->helper->getGoodsDescription($quote)).'",
                    "origin":"'.$origCountry.'",
                    "productType":"XPS",
                    "serviceType":"'.$serviceCode.'",
                    "shipAdd1":"'.(isset($client_name) ? $client_name : '').'",
                    "shipAdd2":"'.(isset($client_address) ? $client_address : '').'",
                    "shipCity":"",
                    "shipContPerson":"'.(isset($client_name) ? $client_name : '').'",
                    "shipCountry":"'.$origCountry.'",
                    "shipFax":"",
                    "shipName":"'.(isset($client_name) ? $client_name : '').'",
                    "shipPh":"'.(isset($client_phone) ? $client_phone : '').'",
                    "shipperRef":"'.$order->getIncrementId().'",
                    "specialInstruction":"'.$this->escape($specialInstruction).'",
                    "valueOfShipment":"'.$total.'",
                    "weight":"'.$weight.'"
            }';

                $this->debug('timeexpressparcels tracking request');
                $this->debug($json_string);

                $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->curl->setOption(CURLOPT_ENCODING, '');
                $this->curl->setOption(CURLOPT_TIMEOUT, 0);
                $this->curl->setOption(CURLOPT_MAXREDIRS, 10);
                $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
                $this->curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                $this->curl->addHeader("Content-Type", "application/json");

                $this->curl->post(self::CREATE_AWB_URL, $json_string);
                $response = $this->curl->getBody();
                $this->debug('timeexpressparcels tracking response');

                if ($response) {
                    $this->debug($response);
                    $response = utf8_encode($response);
                    $response_json = json_decode($response, true);

                    if ($response_json['code'] == 1) {
                        $awbNo = $response_json['awbNo'];
                        $this->helper->updateTrackingData($order, $quote, $awbNo);
                    }
                } else {
                    $error = __("Curl Error");
                    $this->debug($error);
                }
            } catch (\Exception $e) {
                $this->debug('timeexpressparcels tracking Exception: '.$e->getMessage());
            }
        }
    }

    public function debug($message)
    {
        $debug = true;
        if ($debug) {
            $this->logger->debug($message);
        }
    }

    public function escape($data)
    {
        return $this->escaper->escapeQuote($data);
    }
}

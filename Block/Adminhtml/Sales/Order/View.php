<?php
namespace TimeExpressParcels\TimeExpressParcels\Block\Adminhtml\Sales\Order;

use TimeExpressParcels\TimeExpressParcels\Model\OrderFactory as TimeExpressParcelsOrder;

class View extends \Magento\Backend\Block\Template
{
    protected $helper;
    protected $request;
    protected $orderFactory;
    protected $timeexpressparcelsOrder;
    
    public function __construct(
        \TimeExpressParcels\TimeExpressParcels\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Backend\Block\Template\Context $context,
        TimeExpressParcelsOrder $timeexpressparcelsOrder,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->orderFactory = $orderFactory;
        $this->timeexpressparcelsOrder = $timeexpressparcelsOrder;
        parent::__construct($context, $data);
    }

    public function getResponseValues()
    {
        $order_id = (int)$this->request->getParam('order_id');
        $order = $this->orderFactory->create()->load($order_id);
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
            $quoteId = $order->getQuoteId();
            $model = $this->timeexpressparcelsOrder->create();
            $model = $model->load($quoteId, 'quote_id');
            if ($model && $model->getId() > 0) {
                $model->load($model->getId());
                $awbNo =  $model->getAwbno();
                $html = $awbNo;
                if ($awbNo) {
                    $trackUrl = 'https://www.timexpress.ae/track/?awbno='.$awbNo;
                    $html = $awbNo.' : <a target="blank" href="'.$trackUrl.'">Track Here</a>';
                } else {
                    $createUrl = $this->getUrl('timeexpressparcels/order/track', [
                        'order_id'=>$quoteId,
                        'from' => 'order_view',
                        'order' => $order_id
                    ]);
                    $html ='<a href="'.$createUrl.'">Create Tracking Number</a>';
                }
                return $html;
            }
        }
        return false;
    }
}

<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Controller\Adminhtml\Order;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TimeExpressParcels\TimeExpressParcels\Helper\Data as TimeExpressParcelsHelper;
use TimeExpressParcels\TimeExpressParcels\Model\Api as TimeExpressParcelsApi;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Validator\Exception as MagentoException;

class Track extends Action
{
    protected $_coreRegistry;

    protected $_resultPageFactory;

    protected $reportFactory;
    
    protected $helper;
    
    protected $api;
    
    protected $orderRepository;
    
    protected $quoteFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        TimeExpressParcelsHelper $helper,
        TimeExpressParcelsApi $api,
        \Magento\Sales\Model\OrderFactory $orderRepository,
        QuoteFactory $quoteFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->api = $api;
        $this->orderRepository = $orderRepository;
        $this->quoteFactory = $quoteFactory;
    }
    
    public function execute()
    {
        try {
            $quoteId = (int)strip_tags(trim($this->getRequest()->getParam('order_id')));

            if ($quoteId) {
                $order = $this->orderRepository->create()->load($quoteId, 'quote_id');
                $quote = $this->quoteFactory->create()->load($quoteId);
                if ($order && $order->getId() > 0) {
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
                        $this->api->generateTrackingNumber($order, $quote);
                        $this->helper->sendTrackingEmail($order, $quote);
                        $successMessage = __('Time Express Parcels Tracking has been created successfully.');
                        $this->messageManager->addSuccess($successMessage);

                        if ($this->getRequest()->getParam('from')) {
                            $orderId = $this->getRequest()->getParam('order');
                            $this->_redirect($this->getUrl('sales/order/view/', ['order_id'=>$orderId]));
                        } else {
                            $this->_redirect('*/*/index');
                        }
                    } else {
                        throw new MagentoException(__('This order not eligible for Time Express Parcels'));
                    }

                } else {
                    throw new MagentoException(__('Invalid Order'));
                }
            } else {
                throw new MagentoException(__('Invalid Order'));
            }
        } catch (MagentoException $ex) {
            $this->messageManager->addError(__('Tracking Creation Failed: '). $ex->getMessage());
            if ($this->getRequest()->getParam('from')) {
                $orderId = $this->getRequest()->getParam('order');
                $this->_redirect($this->getUrl('sales/order/view/', ['order_id'=>$orderId]));
            } else {
                $this->_redirect('*/*/index');
            }
        }
    }
}

<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Controller\Adminhtml\Order;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TimeExpressParcels\TimeExpressParcels\Helper\Data as TimeExpressParcelsHelper;
 
class Grid extends Action
{

    protected $_coreRegistry;

    protected $_resultPageFactory;

    protected $reportFactory;
    
    protected $helper;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        TimeExpressParcelsHelper $helper
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('TimeExpressParcels_TimeExpressParcels::order');
    }
    
    public function execute()
    {
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('TimeExpressParcels_TimeExpressParcels::order');
        $resultPage->getConfig()->getTitle()->prepend(__('Time Express Parcels Orders'));
        return $resultPage;
    }
}

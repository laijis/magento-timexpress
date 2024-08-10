<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Controller\Adminhtml\Account;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use TimeExpressParcels\TimeExpressParcels\Helper\Data as TimeExpressParcelsHelper;
 
class Index extends Action
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
 
    public function execute()
    {
        $isPost = $this->getRequest()->getPost();
        if ($isPost) {
            if ($this->getRequest()->getParam('tes_login')) {
                $username = strip_tags(trim($this->getRequest()->getParam('tes_username')));
                $password = strip_tags(trim($this->getRequest()->getParam('tes_password')));
                
                if (empty($username) || empty($password)) {
                    $this->messageManager->addError(__('Please enter login credentials'));
                } else {
                    $response = $this->helper->login($username, $password);
                    if ($response['status'] == 0) {
                        $this->messageManager->addError($response['message']);
                    } else {
                        $this->messageManager->addSuccess(__('Authenticated with Time Express Parcels successfully.'));
                    }
                }
            }
        }
        
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('TimeExpressParcels_TimeExpressParcels::account');
        $resultPage->getConfig()->getTitle()->prepend(__('Time Express Parcels Account'));
 
        return $resultPage;
    }
}

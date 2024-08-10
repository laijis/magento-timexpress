<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Block\Adminhtml\Renderer;
 
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
 
class Tracking extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $_storeManager;
    
    protected $backendUrlManager;
        
    protected $formKey;

    public function __construct(
        StoreManagerInterface $_storeManager,
        \Magento\Backend\Model\Url $backendUrlManager,
        \Magento\Framework\Data\Form\FormKey $formKey
    ) {
        $this->_storeManager = $_storeManager;
        $this->backendUrlManager  = $backendUrlManager;
        $this->formKey = $formKey;
    }
 
    public function render(DataObject $row)
    {
        $awbNo =  $row->getAwbno();
        $html = $awbNo;
        if ($awbNo) {
            $html = $awbNo.' : <a target="blank" href="https://www.timexpress.ae/track/?awbno='.$awbNo.'">'
                    . 'Track Here</a>';
        } else {
            $html ='<a href="'.$this->backendUrlManager->getUrl(
                'timeexpressparcels/order/track',
                ['order_id'=>$row->getQuoteId()]
            ).'">Create Tracking Number</a>';
        }
        return $html;
    }
}

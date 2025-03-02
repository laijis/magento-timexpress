<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Block\Adminhtml\Renderer;
 
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
 
class ShippingAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $_storeManager;
    
    public function __construct(
        StoreManagerInterface $_storeManager
    ) {
        $this->_storeManager = $_storeManager;
    }
 
    public function render(DataObject $row)
    {
        $amount =  $row->getShippingTotal();
        $html = $amount;
        return $html;
    }
}

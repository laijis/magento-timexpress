<?php
 
namespace TimeExpressParcels\TimeExpressParcels\Block\Adminhtml;
 
use Magento\Backend\Block\Widget\Grid\Container;
 
class Order extends Container
{
   /**
    * Constructor
    *
    * @return void
    */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_order';
        $this->_blockGroup = 'TimeExpressParcels_TimeExpressParcels';
        $this->_headerText = __('TimeExpressParcels Orders');
    
        parent::_construct();
        $this->buttonList->remove('add');
    }
}

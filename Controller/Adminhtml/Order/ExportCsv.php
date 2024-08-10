<?php
namespace TimeExpressParcels\TimeExpressParcels\Controller\Adminhtml\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCsv extends \TimeExpressParcels\TimeExpressParcels\Controller\Adminhtml\Order
{
    public function execute()
    {
        $this->_view->loadLayout();
        $fileName = 'timeexpressparcels_orders.csv';
        $content = $this->_view->getLayout()->getChildBlock('timeexpressparcels_order.grid', 'grid.export');

        return $this->_fileFactory->create(
            $fileName,
            $content->getCsvFile($fileName),
            DirectoryList::VAR_DIR
        );
    }
}

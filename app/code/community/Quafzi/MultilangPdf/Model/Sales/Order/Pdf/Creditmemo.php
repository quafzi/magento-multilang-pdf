<?php
/**
 * @category    Quafzi
 * @package     Quafzi_MultilangPdf
 * @copyright   Copyright (c) 2015 Thomas Birke <magento@netextreme.de>
 */

/**
 * Rewrite Sales Order Creditmemo PDF model to duplicate creditmemo in another language
 */
class Quafzi_MultilangPdf_Model_Sales_Order_Pdf_Creditmemo extends Mage_Sales_Model_Order_Pdf_Creditmemo
{
    public function getPdf($creditmemos = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('creditmemo');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        $additionalLocale = Mage::getStoreConfig('quafzi_multilangpdf/languages/additional');

        foreach ($creditmemos as $creditmemo) {
            if (Mage::app()->getStore()->getId() === $creditmemo->getStoreId()) {
                // Called from frontend
                $this->_renderCreditmemo($pdf, $creditmemo);
            } else {
                // Called from backend
                $this->_renderCreditmemo($pdf, $creditmemo);
                $this->_renderCreditmemo($pdf, $creditmemo, $additionalLocale);
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    protected function _renderCreditmemo($pdf, $creditmemo, $locale)
    {
        if ($creditmemo->getStoreId()) {
            Mage::app()->getLocale()->emulate($creditmemo->getStoreId());
            Mage::app()->setCurrentStore($creditmemo->getStoreId());
            Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
        }

        /**
         * The following code is taken unmodified from the base class
         */
        $page  = $this->newPage();
        $order = $creditmemo->getOrder();
        /* Add image */
        $this->insertLogo($page, $creditmemo->getStore());
        /* Add address */
        $this->insertAddress($page, $creditmemo->getStore());
        /* Add head */
        $this->insertOrder(
            $page,
            $order,
            Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID, $order->getStoreId())
        );
        /* Add document text and number */
        $this->insertDocumentNumber(
            $page,
            Mage::helper('sales')->__('Credit Memo # ') . $creditmemo->getIncrementId()
        );
        /* Add table head */
        $this->_drawHeader($page);
        /* Add body */
        foreach ($creditmemo->getAllItems() as $item){
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            /* Draw item */
            $this->_drawItem($item, $page, $order);
            $page = end($pdf->pages);
        }
        /* Add totals */
        $this->insertTotals($page, $creditmemo);
    }
}

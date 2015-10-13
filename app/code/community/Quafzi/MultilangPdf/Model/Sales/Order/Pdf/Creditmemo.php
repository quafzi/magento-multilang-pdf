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
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;

        $order = $creditmemo->getOrder();

        /* Add image */
        $this->insertLogo($page, $creditmemo->getStore());

        /* Add address */
        $this->insertAddress($page, $creditmemo->getStore());

        /* Add head */
        $this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_CREDITMEMO_PUT_ORDER_ID, $order->getStoreId()));

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page);
        $page->drawText(Mage::helper('sales')->__('Credit Memo # ') . $creditmemo->getIncrementId(), 35, 780, 'UTF-8');

        /* Add table head */
        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y-15);
        $this->y -=10;
        $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
        $this->_drawHeader($page);
        $this->y -=15;

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        /* Add body */
        foreach ($creditmemo->getAllItems() as $item){
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            if ($this->y<20) {
                $page = $this->newPage(array('table_header' => true));
            }

            /* Draw item */
            $page = $this->_drawItem($item, $page, $order);
        }

        /* Add totals */
        $page = $this->insertTotals($page, $creditmemo);
    }
}

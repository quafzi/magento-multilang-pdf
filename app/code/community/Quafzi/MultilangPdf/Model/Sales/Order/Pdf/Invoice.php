<?php
/**
 * @category    Quafzi
 * @package     Quafzi_MultilangPdf
 * @copyright   Copyright (c) 2015 Thomas Birke <magento@netextreme.de>
 */

/**
 * Rewrite Sales Order Invoice PDF model to duplicate invoice in another language
 */
class Quafzi_MultilangPdf_Model_Sales_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);

        $additionalLocale = Mage::getStoreConfig('quafzi_multilangpdf/languages/additional');

        foreach ($invoices as $invoice) {
            if (Mage::app()->getStore()->getId() === $invoice->getStoreId()) {
                // Called from frontend
                $this->_renderInvoice($pdf, $invoice);
            } else {
                // Called from backend
                $this->_renderInvoice($pdf, $invoice);
                $this->_renderInvoice($pdf, $invoice, $additionalLocale);
            }
        }

        $this->_afterGetPdf();

        return $pdf;
    }

    protected function _renderInvoice($pdf, $invoice, $locale)
    {
        if ($invoice->getStoreId()) {
            Mage::app()->getLocale()->emulate($invoice->getStoreId());
            Mage::app()->setCurrentStore($invoice->getStoreId());
            Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
        }

        /**
         * The following code is taken unmodified from the base class
         */
        $page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
        $pdf->pages[] = $page;

        $order = $invoice->getOrder();

        /* Add image */
        $this->insertLogo($page, $invoice->getStore());

        /* Add address */
        $this->insertAddress($page, $invoice->getStore());

        /* Add head */
        $this->insertOrder($page, $order, Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId()));


        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page);
        $page->drawText(Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId(), 35, 780, 'UTF-8');

        /* Add table */
        $page->setFillColor(new Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);

        $page->drawRectangle(25, $this->y, 570, $this->y -15);
        $this->y -=10;

        /* Add table head */
        $page->setFillColor(new Zend_Pdf_Color_RGB(0.4, 0.4, 0.4));
        $page->drawText(Mage::helper('sales')->__('Products'), 35, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('SKU'), 255, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Price'), 380, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Qty'), 430, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Tax'), 480, $this->y, 'UTF-8');
        $page->drawText(Mage::helper('sales')->__('Subtotal'), 535, $this->y, 'UTF-8');

        $this->y -=15;

        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

        /* Add body */
        foreach ($invoice->getAllItems() as $item){
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            if ($this->y < 15) {
                $page = $this->newPage(array('table_header' => true));
            }

            /* Draw item */
            $page = $this->_drawItem($item, $page, $order);
        }

        /* Add totals */
        $page = $this->insertTotals($page, $invoice);

        if ($invoice->getStoreId()) {
            Mage::app()->getLocale()->revert();
        }
    }
}

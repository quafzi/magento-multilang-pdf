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
        $page  = $this->newPage();
        $order = $invoice->getOrder();
        /* Add image */
        $this->insertLogo($page, $invoice->getStore());
        /* Add address */
        $this->insertAddress($page, $invoice->getStore());
        /* Add head */
        $this->insertOrder(
            $page,
            $order,
            Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
        );
        /* Add document text and number */
        $this->insertDocumentNumber(
            $page,
            Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
        );
        /* Add table */
        $this->_drawHeader($page);
        /* Add body */
        foreach ($invoice->getAllItems() as $item){
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            /* Draw item */
            $this->_drawItem($item, $page, $order);
            $page = end($pdf->pages);
        }
        /* Add totals */
        $this->insertTotals($page, $invoice);
        if ($invoice->getStoreId()) {
            Mage::app()->getLocale()->revert();
        }
    }
}

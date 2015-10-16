<?php
/**
 * @category    Quafzi
 * @package     Quafzi_MultilangPdf
 * @copyright   Copyright (c) 2015 Thomas Birke <magento@netextreme.de>
 */

/**
 * Rewrite Symmetrics Invoice PDF model to duplicate invoice in another language
 */
class Quafzi_MultilangPdf_Model_Symmetrics_InvoicePdf_Invoice extends Symmetrics_InvoicePdf_Model_Pdf_Invoice
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
            if (Mage::getSingleton('admin/session')->getUser() && Mage::getSingleton('admin/session')->getUser()->getId()) {
                // Called from backend
                Mage::log('backend', false, 'tbi.log');
                $this->_renderInvoice($pdf, $invoice);
                $this->_renderInvoice($pdf, $invoice, $additionalLocale);
            } else {
                // Called from frontend
                Mage::log('frontend', false, 'tbi.log');
                $this->_renderInvoice($pdf, $invoice);
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

        $this->_invoice = $invoice;

        $settings = new Varien_Object();
        $order = $invoice->getOrder();

        $settings->setStore($invoice->getStore());

        $page = $this->newPage($settings);

        /* Add image */
        $this->insertLogo($page, $invoice->getStore());
        $this->setSubject($page, Mage::helper('sales')->__('Invoice'));
        /* Add head */
        $this->insertOrder(
            $page,
            $order,
            Mage::helper('invoicepdf')->getSalesPdfInvoiceConfigFlag(
                self::PDF_INVOICE_PUT_ORDER_ID,
                $order->getStoreId()
            )
        );

        /* Add body */
        foreach ($invoice->getAllItems() as $item) {
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            /* Draw item */
            $page = $this->_drawItem($item, $page, $order);
        }

        $font = $this->_setFontRegular($page);
        $this->_newLine($font, 10);
        /* Add additional info */
        $page = $this->_insertAdditionalInfo($page, $order);
        /* Add totals */
        $page = $this->insertTotals($page, $invoice);


        $this->_newLine($font, 20);

        /* Insert info text */
        $page = $this->_insertInfoText($page, $order);

        /* Insert info box */
        $page = $this->_insertInfoBlock($page, $order);

        if ($invoice->getStoreId()) {
            Mage::app()->getLocale()->revert();
        }
    }
}

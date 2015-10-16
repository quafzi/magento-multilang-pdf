.. sectnum::

.. contents:: Contents

Intention
=========

Your customers want to see the invoices in their own language, but you need it
in some other language for legal reasons?

Install this extension and you'll get invoices and creditmemos in two languages
(when printed from the admin backend).

Installation
============

To install this extension, you should use modman_:

::

    modman clone https://github.com/quafzi/Magento-MultilangPdfs.git

.. _modman: https://github.com/colinmollenhour/modman

Configuration
=============

At the moment, there is no configuration in Magento Backend. Invoices and
Creditmemos will be printed in english, additionally.

If you need to change this language, please change ``en_US`` to your required
locale in ``app/code/community/Quafzi/MultilangPdfs/etc/config.xml``.

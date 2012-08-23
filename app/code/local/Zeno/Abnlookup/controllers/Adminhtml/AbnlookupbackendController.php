<?php
class Zeno_Abnlookup_Adminhtml_AbnlookupbackendController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("ABN Lookup"));
	   $this->renderLayout();
    }
}
<?php
class Zeno_Abnlookup_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("ABN Lookup"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("abn lookup", array(
                "label" => $this->__("ABN Lookup"),
                "title" => $this->__("ABN Lookup")
		   ));

      $this->renderLayout(); 
	  
    }
}
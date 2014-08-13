<?php
/**
 * @copyright   Copyright (c) 2009-2011 Amasty (http://www.amasty.com)
 */ 
class Amasty_Perm_Model_Observer
{
    protected $_permissibleActions = array('index', 'grid', 'exportCsv', 'exportExcel');
    protected $_exportActions = array('exportCsv', 'exportExcel');
    protected $_controllerNames = array('sales_', 'orderspro_');

    public function handleAdminUserSaveAfter($observer) 
    {
        $editor = Mage::getSingleton('admin/session')->getUser();
        if (!$editor) // API or smth else
            return $this;  
             
        $user = $observer->getDataObject(); 
        if ($editor->getId() == $user->getId()){ // My Account
            return $this;     
        }

        $str = '';
        if ($user->getCustomerGroupId()) {
            $str = implode(",", $user->getCustomerGroupId());
        }
        Mage::getModel('amperm/perm')->getResource()->assignGroups($user->getId(), $str);
                 
        $ids = $user->getSelectedCustomers();
        if (is_null($ids))
            return $this;        
        $ids = Mage::helper('adminhtml/js')->decodeGridSerializedInput($ids);
        
        Mage::getModel('amperm/perm')->assignCustomers($user->getId(), $ids);
        
        return $this;           
    }
    
    public function handleOrderCollectionLoadBefore($observer) 
    {
        if ('amperm' == Mage::app()->getRequest()->getModuleName())
            return $this;
            
        $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
        if ($uid){
            $permissionManager = Mage::getModel('amperm/perm');
            $collection = $observer->getOrderGridCollection();
            if ($collection){
                $permissionManager->addOrdersRestriction($collection, $uid);
            }
            else {
                $keys = array_keys($observer->getData());
                $collection = $observer->getData($keys[1]);
                $permissionManager->addOrderDataRestriction($collection, $uid);
            }
        }       
        
        return $this;    
    }
    
    public function handleCustomerCollectionLoadBefore($observer) 
    {
        $collection = $observer->getCollection();
        if (strpos(get_class($collection),'Customer_Collection')){
            $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
            if ($uid){
                $permissionManager = Mage::getModel('amperm/perm');
                $permissionManager->addCustomersRestriction($collection, $uid);
            }         
        }
        
        return $this;    
    } 
       
    public function handleCustomerSaveAfter($observer) 
    {
        //registration form
        $uid = Mage::app()->getRequest()->getParam('sales_person');
        if ($this->_isAdmin()){
            $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
        }
        
        if ($uid){
            Mage::getModel('amperm/perm')->assignOneCustomer($uid, $observer->getCustomer()->getId());    
        }  
               
        return $this; 
    }
    
    public function handleOrderCreated($observer) 
    {
        $user = null;
        
        $isGuest = false;
        $orders = $observer->getOrders(); // multishipping
        if (!$orders){ // all other situalions like single checkout, goofle checkout, admin 
            $orders  = array($observer->getOrder());
            $isGuest = $orders[0]->getCustomerIsGuest();
        }

        if ($this->_isAdmin()){
            $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
            if ($uid){
                Mage::getModel('amperm/perm')->assignOneOrder($uid, $orders[0]->getId());
                $user = Mage::getSingleton('admin/session')->getUser();
            } else {
                $uid = Mage::getModel('amperm/perm')->assignOrderByCustomer($orders[0]->getCustomerId(), $orders[0]->getId());
                $user = Mage::getModel('admin/user')->load($uid);
            }
        }
        elseif (!$isGuest) {
            foreach ($orders as $order){
                $uid = Mage::getModel('amperm/perm')->assignOrderByCustomer($order->getCustomerId(), $order->getId());
            }
            $user = Mage::getModel('admin/user')->load($uid);
        }
        
        // send email
        if (Mage::getStoreConfig('amperm/general/send_email') && $user){
        	
        	/*
        	 * Get Sales Man email
        	 */
        	$emails = array(
        		$user->getEmail()
        	);
        	
        	/*
        	 * Get additional emails to send
        	 */
        	$additionalEmails = $user->getEmails();
        	if (!empty($additionalEmails)) {
        		$additionalEmails = explode(",", $additionalEmails);
        		if (is_array($additionalEmails)) {
        			foreach ($additionalEmails as $email) {
        				$emails[] = trim($email);
        			}
        		}
        	}
        	        	
        	foreach ($emails as $email) {
	            foreach ($orders as $order){
	                try {
	                    $this->_sendEmail($email, $order);
	                } 
	                catch (Exception $e) {
	                    print_r($e);
	                    Mage::logException($e);
	                }   
	            }              
        	}
        }

        return $this;
    }

    // for old versions
    public function handleCoreCollectionAbstractLoadBefore($observer)
    {
        if (!Mage::helper('ambase')->isVersionLessThan(1, 4, 2))
            return;
        
        $collection = $observer->getCollection();
        if ($collection instanceof Mage_Sales_Model_Mysql4_Order_Grid_Collection)
        {
            $mod  = Mage::app()->getRequest()->getModuleName();
            $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
            if ($uid && 'amperm' != $mod){
                $permissionManager = Mage::getModel('amperm/perm');
                if ($collection){
                    $permissionManager->addOrdersRestriction($collection, $uid);
                }
            }
        }
    }
       
    protected function _sendEmail($to, $order)
    {
        if (!Mage::getStoreConfig('amperm/general/send_email')){
            return;
        }
            
        if (!Mage::helper('sales')->canSendNewOrderEmail($order->getStoreId())) {
            return;
        }

        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true);
        $paymentBlock->getMethod()->setStore($order->getStoreId());

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */


        $mailTemplate->setDesignConfig(array('area'=>'frontend', 'store'=>$order->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig('sales_email/order/template', $order->getStoreId()),
                Mage::getStoreConfig('sales_email/order/identity', $order->getStoreId()),
                $to,
                null,
                array(
                    'order'         => $order,
                    'billing'       => $order->getBillingAddress(),
                    'payment_html'  => $paymentBlock->toHtml(),
                )
            );
            
        $translate->setTranslateInline(true);
    }
    
    protected function _isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin())
            return true;
        // for some reason isAdmin does not work here
        if (Mage::app()->getRequest()->getControllerName() == 'sales_order_create')
            return true;
            
        return false;
    }
   
    protected function _isControllerName($place)
    {
        $found = false;
        foreach ($this->_controllerNames as $controllerName) {
            if (Mage::app()->getRequest()->getControllerName() == $controllerName . $place) {
                $found = true;
            }
        }
        return $found;
    }

    protected function _prepareColumns(&$grid, $export = false, $place = 'order', $after = 'entity_id')
    {
        if (!$this->_isControllerName($place) ||
            !in_array(Mage::app()->getRequest()->getActionName(), $this->_permissibleActions))
            return $grid;

        $column = array(
            'header'       => Mage::helper('amperm')->__('Dealer'),
            'type'         => 'options',
            'align'        => 'center',
            'index'        => 'uid',
            'renderer'     => 'amperm/adminhtml_renderer_dealer',
        );
        $grid->addColumnAfter($column['index'], $column, $after);

        return $grid;
    }

    public function handleCoreLayoutBlockCreateAfter($observer)
    {
        $block = $observer->getBlock();
        $hlp = Mage::helper('amperm');
        $uid = $hlp->getCurrentSalesPersonId();

        if (!$uid) {
            $blockClass = Mage::getConfig()->getBlockClassName('adminhtml/sales_order_grid');
            if ($blockClass == get_class($block)) {
                $this->_prepareColumns($block, in_array(Mage::app()->getRequest()->getActionName(), $this->_exportActions));
            }
        }

        $blockClass = Mage::getConfig()->getBlockClassName('adminhtml/customer_edit');
        if ($blockClass == get_class($block)) {
            if ($customerId = $block->getCustomerId()) {
                $key = 'gh5fu!,dh2jd73po';
                $permKey = md5($customerId . $key);
                $block->addButton('customer_login', array(
                    'label'   => Mage::helper('amperm')->__('Log In as Customer'),
                    'onclick' => 'window.open(\'' . Mage::helper('adminhtml')->getUrl('amperm/adminhtml_perm/login', array('customer_id' => $customerId, 'perm_key' => $permKey)).'\', \'customer\');',
                    'class'   => 'back',
                ), 0, 1);
            }
        }
    }

    public function handleCoreBlockAbstractToHtmlAfter($observer)
    {
        $block = $observer->getBlock();
        $transport = $observer->getTransport();
        $html = $transport->getHtml();

        $blockClass = Mage::getConfig()->getBlockClassName('adminhtml/sales_order_view_info');
        if ($blockClass == get_class($block)
            && false === strpos($html, 'amperm_form')
            && Mage::getStoreConfig('amperm/general/reassign_fields')
            && $this->_isControllerName('order')) {
            $tempPos = strpos($html, '<!--Account Information-->');
            if (false !== $tempPos) {
                $pos = strpos($html, '</table>', $tempPos);
                $insert = Mage::app()->getLayout()->createBlock('amperm/adminhtml_info')->setOrderId($block->getOrder()->getId())->toHtml();
                $html = substr_replace($html, $insert, $pos-1, 0);
            }
        }

        $transport->setHtml($html);
    }
}
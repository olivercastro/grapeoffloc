<?php
/**
 * @copyright   Copyright (c) 2009-2011 Amasty (http://www.amasty.com)
 */
class Amasty_Perm_Adminhtml_PermController extends Mage_Adminhtml_Controller_Action
{
    public function reportsAction() 
	{
	    $this->getResponse()->setBody(
	           $this->getLayout()->createBlock('amperm/adminhtml_reports')->toHtml()
	    ); 
	}    
    
    public function relationAction() 
	{
        $grid = $this->getLayout()->createBlock('amperm/adminhtml_relation')
                    ->setSelectedCustomers($this->getRequest()->getPost('selected_customers', null));
        
        // get serializer block html if needed
        $serializerHtml = ''; 
        if ($this->isFirstTime()){
            $serializer = $this->getLayout()->createBlock('adminhtml/widget_grid_serializer');
            $serializer->initSerializerBlock($grid, 'getSavedCustomers', 'selected_customers', 'selected_customers');
            $serializerHtml = $serializer->toHtml();
        } 
                
	    $this->getResponse()->setBody(
	           $grid->toHtml() . $serializerHtml
	    ); 
	}
	
	private function isFirstTime()
	{
	    $res = true;
	    
        $params = $this->getRequest()->getParams();
        $keys   = array('sort', 'filter', 'limit', 'page');
        
        foreach($keys as $k){
            if (array_key_exists($k, $params))
                $res = false;
        }
        
        return $res;	    
	}
	
	public function assignAction()
	{
	    $data = $this->getRequest()->getParam('amperm');

        $msg = array();

        if ($data['old_dealer'] != $data['new_dealer']) {
            Mage::getModel('amperm/perm')->updateOrder($data['order_id'], $data['new_dealer']);
            $msg[] = Mage::helper('amperm')->__('Order has been successfully assigned');
        }

        if (Mage::getStoreConfig('amperm/messages/enabled')) {
            $oldEmail = Mage::getStoreConfig('amperm/messages/admin_email');
            if ($data['old_dealer']){ // not admin
                $dealer   = Mage::getModel('admin/user')->load($data['old_dealer']);
                $oldEmail = $dealer->getEmail(); 
            }
            
            $newEmail = Mage::getStoreConfig('amperm/messages/admin_email');
            if ($data['new_dealer']){ // not admin
                $dealer   = Mage::getModel('admin/user')->load($data['new_dealer']);
                $newEmail = $dealer->getEmail(); 
            }
    
            $translate = Mage::getSingleton('core/translate');
            $translate->setTranslateInline(false);
            
            $dealers = Mage::helper('amperm')->getSalesPersonList();
            if (isset($dealers[$data['old_dealer']])) {
                $fromName = $dealers[$data['old_dealer']];
            } else {
                $fromName = Mage::helper('amperm')->__('Admin');
            }
            if (isset($dealers[$data['new_dealer']])) {
                $toName = $dealers[$data['new_dealer']];
            } else {
                $toName = Mage::helper('amperm')->__('Admin');
            }

            $order = Mage::getModel('sales/order')->load($data['order_id']);
            $tpl = Mage::getModel('core/email_template');
            $tpl->setDesignConfig(array('area'=>'frontend'))
                ->sendTransactional(
                    Mage::getStoreConfig('amperm/messages/template'),
                    Mage::getStoreConfig('amperm/messages/identity'),
                    array($oldEmail, $newEmail),
                    '',
                    array(
                        'order_id'  => $data['order_id'],
                        'increment_id' => $order->getIncrementId(),
                        'comment'   => $data['txt'],
                        'newdealer' => $toName,
			            'olddealer' => $fromName,
		            )
                );
            $translate->setTranslateInline(true);	 
        }

        if ($data['old_dealer'] != $data['new_dealer']
            || $data['txt']) {
            $messageModel = Mage::getModel('amperm/message')
                ->setOrderId($data['order_id'])
                ->setFromId($data['old_dealer'])
                ->setToId($data['new_dealer'])
                ->setTxt($data['txt'])
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setAuthorId(Mage::helper('amperm')->getCurrentSalesPersonId());
            $messageModel->save();
        }

        if ($data['txt']) {
            $msg[] = Mage::helper('amperm')->__('Comment has been successfully added');
        }

        if (!empty($msg)) {
            foreach ($msg as $message)
                Mage::getSingleton('adminhtml/session')->addSuccess($message);
        } else {
            $message = Mage::helper('amperm')->__('Please write comment and/or assign order to another dealer.');
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        $uid = Mage::helper('amperm')->getCurrentSalesPersonId();
        if ($uid
            && $data['old_dealer'] != $data['new_dealer']) {
            $this->_redirect('adminhtml/sales_order/index');
        } else {
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $data['order_id']));
        }
    }

    public function exportCsvAction()
    {
        $content = $this->getLayout()->createBlock('amperm/adminhtml_reports')
            ->getCsvFile();
        $this->_prepareDownloadResponse('reports.csv', $content);  
    }
    
    public function loginAction()
    {
        $session    = Mage::getSingleton('admin/session');
        $id = $this->getRequest()->getParam('customer_id');
        $permKey = $this->getRequest()->getParam('perm_key');
        $customer   = Mage::getModel('customer/customer')->load($id);
        $key = 'gh5fu!,dh2jd73po';
                
        if (($permKey !== md5($id . $key)) || !$customer->getId()) {
            return $this->_redirect('admin/');
        }

        $hash  = md5(uniqid(mt_rand(), true));
        $login = Mage::getModel('amperm/login')
            ->setLoginHash($hash)
            ->setCustomerId($id)
            ->save();
            
        if ($customer->getWebsiteId() != Mage::app()->getWebsite()->getId()) {
            $website = Mage::app()->getWebsite($customer->getWebsiteId());
            $url = $website->getDefaultStore()->getBaseUrl() . 'index.php/ampermfront/index/index/id/' . $hash;
            return $this->_redirectUrl($url);
        }
            
        return $this->_redirect('ampermfront/', array(
            'id'     => $hash, 
            '_store' => $customer->getStoreId(), 
            ));
    }
}
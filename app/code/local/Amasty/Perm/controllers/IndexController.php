<?php
/**
* @author Amasty Team
* @copyright Copyright (c) Amasty (http://www.amasty.com)
* @package Amasty_Perm
*/
class Amasty_Perm_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $hash  = $this->getRequest()->getParam('id');
        $login = Mage::getModel('amperm/login')->load($hash, 'login_hash');
        $customerId = $login->getCustomerId();

        if ($customerId) {
            $login->delete();
            $session = Mage::getSingleton('customer/session');
            $session->renewSession()
                ->loginById($customerId);

            return $this->_redirect('customer/account/');
        }

        return $this->_redirect('');
    }
}
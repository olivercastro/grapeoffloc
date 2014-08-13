<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (http://www.amasty.com)
 * @package Amasty_Perm
 */
class Amasty_Perm_Model_Enterprise_AdminGws_Collections extends Enterprise_AdminGws_Model_Collections
{
    public function limitAdminPermissionUsers($collection)
    {
        $limited = Mage::getResourceModel('enterprise_admingws/collections')
            ->getUsersOutsideLimitedScope(
                $this->_role->getIsAll(),
                $this->_role->getWebsiteIds(),
                $this->_role->getStoreGroupIds()
            );
        $collection->getSelect()->where('main_table.user_id NOT IN (?)', $limited);
    }
}
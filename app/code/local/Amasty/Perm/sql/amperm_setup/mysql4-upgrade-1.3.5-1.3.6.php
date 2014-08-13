<?php
/**
* @author Amasty Team
* @copyright Copyright (c) Amasty (http://www.amasty.com)
* @package Amasty_Perm
*/
$this->startSetup();

$this->run("
	ALTER TABLE `{$this->getTable('amperm/message')}` ADD `author_id` int(10) unsigned NOT NULL default '0';
"); 

$this->endSetup();
<?php   
class Homebase_SimpleFilter_Block_Index extends Mage_Catalog_Block_Product_Abstract{   
    protected $_defaultToolbaBlock='simplefilter/left';
    protected $_productCollection;
    
    protected function _getProductCollection(){
        if(is_null($this->_productCollection)){
            $layer=$this->getLayer();
            if($this->getShowRootCategory()){
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }
            if(Mage::registry('product')){
                $categories=Mage::registry('product')->getCategoryCollection()
                        ->setPage(1,1)
                        ->load();
                if($categories->count()){
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }
            $origCategory=null;
            if($this->getCategoryId()){
                $category=Mage::getModel('catalog/category')->load($this->getCategoryId());
                if($category->getId()){
                    $origCategory=$layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            }
            $this->_productCollection=$layer->getProductCollection();
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            if($origCategory){
                $layer->setCurrentCategory($origCategory);
            }
        }
        return $this->_productCollection;
    }
    public function getLayer(){
        $layer=Mage::registry('current_layer');
        if($layer){
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }
    public function getLoadedProductCollection(){
        return $this->_getProductCollection();
    }
    public function getMode(){
        return $this->getChild('toolbar')->getCurrentMode();
    }
    protected function _beforeToHtml(){
        $toolbar=$this->getToolbarBlock();
        $collection=$this->_getProductCollection();
        if($orders=$this->getAvailableOrders()){
            $toolbar->setAvailableOrders($orders);
        }
        if($sort=$this->getSortBy()){
            $toolbar->setDefaultOrder($sort);
        }
        if($dir=$this->getDefaultDirection()){
            $toolbar->setDefaultDirection($dir);
        }
        if($modes=$this->getModes()){
            $toolbar->setModes($modes);
        }
        $toolbar->setCollection($collection);
        $this->setChild('toolbar',$toolbar);
        Mage::dispatchEvent('catalog_block_product_list_collection',array(
            'collection' =>$this->_getProductCollection()
        ));
        return parent::_beforeToHtml();
    }
    public function getToolbarBlock(){
        if($blockName=$this->getToolbarBlockName()){
            if($block=$this->getLayout()->getBlock($blockName)){
                return $block;
            }
        }
        $block=$this->getLayout()->createBlock($this->_defaultToolbaBlock,microtime());
        return $block;
    }
    public function getAdditionalHtml(){
        return $this->getChildHtml('additional');
    }
    public function getToolbarHtml(){
        return $this->getChildHtml('toolbar');
    }
    public function setCollection($collection){
        $this->_productCollection=$collection;
        return $this;
    }
    public function addAttribute($code){
        $this->_getProductCollection()->addAttributeToSelect($code);
        return $this;
    }
    public function getPriceBlockTemplate(){
        return $this->_getData('price');
    }
}
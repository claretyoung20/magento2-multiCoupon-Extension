<?php

namespace Claret\Coupon\Model\ResourceModel\Post;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Claret\Coupon\Model\Post', 'Claret\Coupon\Model\ResourceModel\Post');
    }
}

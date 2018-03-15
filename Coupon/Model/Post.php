<?php

namespace Claret\Coupon\Model;

use \Magento\Framework\Model\AbstractModel;

class Post extends AbstractModel
{


    /**
     * Initialize resource model
     * @return void
     */
    public function _construct()
    {
        $this->_init('Claret\Coupon\Model\ResourceModel\Post');
    }


}


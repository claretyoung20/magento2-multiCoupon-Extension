<?php

namespace Claret\Coupon\Controller\Adminhtml\World;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{

    public function __construct(Context $context,  PageFactory $resultPageFactory) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Claret_Coupon::test');
        $resultPage->getConfig()->getTitle()->prepend(__('WELCOME MARY..'));
        return $resultPage;
    }
}
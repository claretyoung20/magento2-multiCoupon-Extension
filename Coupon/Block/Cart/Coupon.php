<?php
/**
 * Created by PhpStorm.
 * User: claretyoung
 * Date: 27/02/2018
 * Time: 15:24
 */

namespace Claret\Coupon\Block\Cart;


/**
 * @api
 */
class Coupon extends \Magento\Checkout\Block\Cart\AbstractCart
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $customerSession, $checkoutSession, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getCouponCode()
    {
        return explode (",", $this->getQuote()->getCouponCode());
    }
}

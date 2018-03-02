<?php
/**
 * Created by PhpStorm.
 * User: claretyoung
 * Date: 27/02/2018
 * Time: 16:39
 */

namespace Claret\Coupon\Controller\Cart;

use Magento\Checkout\Controller\Cart;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;


/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CouponPost extends \Magento\Checkout\Controller\Cart
{
    /**
     * Sales quote repository
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    )
    {
        parent::__construct (
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->couponFactory = $couponFactory;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Initialize coupon
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $couponCode = $this->getRequest ()->getParam ('remove') == 1
            ? ''
            : trim ($this->getRequest ()->getParam ('coupon_code'));

        $cartQuote = $this->cart->getQuote ();
        $oldCouponCode = $cartQuote->getCouponCode ();

        $codeLength = strlen ($couponCode);
        if (!$codeLength && !strlen ($oldCouponCode)) {
            return $this->_goBack ();
        }

        try {
            $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

            $itemsCount = $cartQuote->getItemsCount ();
            if ($itemsCount) {
                /** if there is item in the cart*/
                $cartQuote->getShippingAddress ()->setCollectShippingRates (true);

                if ($oldCouponCode) {

                    $couponRemove = $this->getRequest ()->getParam ('removeCouponValue');

                    // split the old coupon into any array
                    $oldCouponArray = explode (',', $oldCouponCode);

                    if ($couponRemove != "") {

                        // remove the coupon if it exist
                        $oldCouponArray = array_diff ($oldCouponArray, array($couponRemove));

                        // change it back to string
                        $oldCouponCode = implode (',', $oldCouponArray);


                        $cartQuote->setCouponCode ($oldCouponCode)->save ();
                    } else {

                        $couponUpdate = $oldCouponCode;

                        // VALIDATE THE COUPON BEFORE SAVING IT
                        $coupon = $this->couponFactory->create ();
                        $coupon->load ($couponCode, 'code');

                        if($coupon->getCode ()) {
                            if (!in_array ($couponCode, $oldCouponArray)) {
                                $couponUpdate = $oldCouponCode . ',' . $couponCode;
                            }
                        }
                        // procede to save
                        $cartQuote->setCouponCode ($isCodeLengthValid ? $couponUpdate : '')->collectTotals ();
                        $cartQuote->setCouponCode ($couponUpdate)->save ();
                    }
                } else {
                    $cartQuote->setCouponCode ($isCodeLengthValid ? $couponCode : '')->collectTotals ();

                }

                $this->quoteRepository->save ($cartQuote);
                /** save the quote */

            }
            $this->quoteRepository->save ($cartQuote);


            if ($codeLength) {
                $escaper = $this->_objectManager->get (Escaper::class);
                $coupon = $this->couponFactory->create ();
                $coupon->load ($couponCode, 'code');
                if (!$itemsCount) {
                    if ($isCodeLengthValid && $coupon->getId ()) {
                        $this->_checkoutSession->getQuote ()->setCouponCode ($oldCouponCode)->save ();
                        $this->messageManager->addSuccess (
                            __ (
                                'You used coupon code "%1".',
                                $escaper->escapeHtml ($couponCode)
                            )
                        );
                    } else {
                        $this->messageManager->addError (
                            __ (
                                'The coupon code "%1" is not valid.',
                                $escaper->escapeHtml ($couponCode)
                            )
                        );
                    }
                } else {
                    /** split the coupon and get the last one */
                    $cSplit = explode (",", $cartQuote->getCouponCode ());

                    if ($isCodeLengthValid && $coupon->getId () && in_array ($couponCode, $cSplit)) {
                        $this->messageManager->addSuccess (
                            __ (
                                'You used coupon code "%1".',
                                $escaper->escapeHtml ($couponCode)
                            )
                        );
                    } else {
                        $this->messageManager->addError (
                            __ (
                                'The coupon code "%1" is not valid.',
                                $escaper->escapeHtml ($couponCode)
                            )
                        );
                    }
                }
            } else {
                $this->messageManager->addSuccess (__ ('You canceled the coupon code.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError ($e->getMessage ());
        } catch (\Exception $e) {
            $this->messageManager->addError (__ ('We cannot apply the coupon code.'));
            $this->_objectManager->get (\Psr\Log\LoggerInterface::class)->critical ($e);
        }

        return $this->_goBack ();
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: claretyoung
 * Date: 01/03/2018
 * Time: 17:17
 */

namespace Claret\Coupon\Model\Quote;


class Discount extends \Magento\SalesRule\Model\Quote\Discount
{
    /**
     * Collect address discount amount
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $store = $this->storeManager->getStore($quote->getStoreId());
        $address = $shippingAssignment->getShipping()->getAddress();
        $this->calculator->reset($address);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $couponCode = $quote->getCouponCode();
        $couponArray = explode(',', $couponCode);

        foreach ($couponArray as $couponCode) {
            $this->_calculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $couponCode);
            $this->_calculator->initTotals($items, $address);

            $eventArgs = array(
                'website_id' => $store->getWebsiteId(),
                'customer_group_id' => $quote->getCustomerGroupId(),
                'coupon_code' => $couponCode,
            );


            $this->calculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getCouponCode());
            $this->calculator->initTotals($items, $address);

            $address->setDiscountDescription([]);
            $items = $this->calculator->sortItemsByPriority($items, $address);

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($items as $item) {
                if ($item->getNoDiscount() || !$this->calculator->canApplyDiscount($item)) {
                    $item->setDiscountAmount(0);
                    $item->setBaseDiscountAmount(0);

                    // ensure my children are zeroed out
                    if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                        foreach ($item->getChildren() as $child) {
                            $child->setDiscountAmount(0);
                            $child->setBaseDiscountAmount(0);
                        }
                    }
                    continue;
                }
                // to determine the child item discount, we calculate the parent
                if ($item->getParentItem()) {
                    continue;
                }

                $eventArgs['item'] = $item;
                $this->eventManager->dispatch('sales_quote_address_discount_item', $eventArgs);

                if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                    $this->calculator->process($item);
                    $this->distributeDiscount($item);
                    foreach ($item->getChildren() as $child) {
                        $eventArgs['item'] = $child;
                        $this->eventManager->dispatch('sales_quote_address_discount_item', $eventArgs);
                        $this->aggregateItemDiscount($child, $total);
                    }
                } else {
                    $this->calculator->process($item);
                    $this->aggregateItemDiscount($item, $total);
                }
            }


            /** Process shipping amount discount */
            $address->setShippingDiscountAmount(0);
            $address->setBaseShippingDiscountAmount(0);
            if ($address->getShippingAmount()) {
                $this->calculator->processShippingAmount($address);
                $total->addTotalAmount($this->getCode(), -$address->getShippingDiscountAmount());
                $total->addBaseTotalAmount($this->getCode(), -$address->getBaseShippingDiscountAmount());
                $total->setShippingDiscountAmount($address->getShippingDiscountAmount());
                $total->setBaseShippingDiscountAmount($address->getBaseShippingDiscountAmount());
            }

            $this->calculator->prepareDescription($address);
            $total->setDiscountDescription($address->getDiscountDescription());
            $total->setSubtotalWithDiscount($total->getSubtotal() + $total->getDiscountAmount());
            $total->setBaseSubtotalWithDiscount($total->getBaseSubtotal() + $total->getBaseDiscountAmount());
            return $this;
        }
    }

}
<?php

namespace Claret\Coupon\Plugin;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Coupon;

class SalesRuleCollection
{

    /**
     * @var Coupon
     */
    private $coupon;

    public function __construct(
        Coupon $coupon
    ) {
        $this->coupon = $coupon;
    }

    public function aroundSetValidationFilter(
        Collection $subject, callable $proceed,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        $address = null
    ) {
        /*Before Start*/
        $validationFilterFlag = $subject->getFlag('validation_filter');
        if (!is_string($couponCode) || strpos($couponCode, ',') === false) {
            $subject->setFlag('validation_filter', true);
        }
        $OrigCouponCode = $couponCode;
        $couponCode = explode(',', $couponCode);

        if (count($couponCode) == 1) {
            /*Start Main function call*/
            $result = $proceed($websiteId,
                $customerGroupId,
                current($couponCode),
                $now,
                $address);
            $subject->setFlag('validation_filter', $validationFilterFlag);
            return $result;
            /*End Main Function Call*/
        }else{
            /*Start : after function call*/

            $result = $proceed($websiteId,
                $customerGroupId,
                $OrigCouponCode,
                $now,
                $address);
//            $result = $subject;
            $ruleIds = [];
            $coupons = explode(',', $OrigCouponCode);
            $select = $result->getSelect();
            $connection = $subject->getConnection();
            $srchRulId = 'rule_id IN (NULL)';
            $replaceCodeIn = $connection->quoteInto(
                'code IN (?)',
                $coupons
            );

            $searchCouponCode = $connection->quoteInto(
                'code = ?',
                $OrigCouponCode
            );
            $selectWhere = empty($select->getPart(\Zend_Db_Select::WHERE))
                ? $select->getPart(\Zend_Db_Select::FROM)['t']['tableName']
                    ->getPart(\Zend_Db_Select::UNION)[1][0]
                    ->getPart(\Zend_Db_Select::FROM)['rule_coupons']
                : $select->getPart(\Zend_Db_Select::WHERE);

            foreach ($selectWhere as &$where) {
                if (strpos($where, $searchCouponCode) !== false) {
                    $where = str_ireplace($searchCouponCode, $replaceCodeIn, $where);
                } elseif (strpos($where, $srchRulId) !== false) {
                    foreach ($coupons as $coupon) {
                        $ruleIds[] = $this->coupon->loadByCode($coupon)->getRuleId();
                    }
                    $replaceRuleIdIN = $connection->quoteInto(
                        'rule_id IN (?)',
                        $ruleIds
                    );
                    $where = str_ireplace($srchRulId, $replaceRuleIdIN, $where);
                }
            }

            if (empty($select->getPart(\Zend_Db_Select::WHERE))) {
                $unionPart =
                    $select->getPart(\Zend_Db_Select::FROM)['t']['tableName']->getPart(\Zend_Db_Select::UNION)[1][0];
                $tmp = $unionPart->getPart(\Zend_Db_Select::FROM);
                $tmp['rule_coupons'] = array_merge($tmp['rule_coupons'], $selectWhere);
                $unionPart->setPart(\Zend_Db_Select::FROM, $tmp);
            } else {
                $select->setPart(\Zend_Db_Select::WHERE, $selectWhere);
            }

            $select->group('rule_id');

            $subject->setFlag('validation_filter', $validationFilterFlag);
            return $result;
            /*End : after function call*/
        }
    }
}

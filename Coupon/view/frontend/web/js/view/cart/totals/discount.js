/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Claret_Coupon/js/view/summary/discount'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Claret_Coupon/cart/totals/discount'
        },

        /**
         * @override
         *
         * @returns {Boolean}
         */
        isDisplayed: function () {
            // this.getPureValue() !== 0; //eslint-disable-line eqeqeq
            return true;
        }
    });
});

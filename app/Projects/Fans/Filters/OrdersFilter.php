<?php

namespace App\Projects\Fans\Filters;

/**
 * 订单相关过滤器
 */
class OrdersFilter
{
    /**
     * 注册所有订单过滤器
     */
    public static function register()
    {
        self::registerRestQuery();
    }

    /**
     * 支持日期查询参数（nice_after/nice_before）
     */
    private static function registerRestQuery()
    {
        add_filter('rest_orders_query', function ($args, $request) {
            if ($before = $request->get_param('nice_before')) {
                $args['date_query']['before'] = $before;
            }

            if ($after = $request->get_param('nice_after')) {
                $args['date_query']['after'] = $after;
            }

            return $args;
        }, 99, 2);
    }
}

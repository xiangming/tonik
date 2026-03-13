<?php

namespace App\Modules\Billing\Services;

use App\Services\BaseService;

/**
 * 计费系统核心服务
 * 
 * 功能：
 * - 获取用户积分信息（含懒惰重置）
 * - 消耗用户积分
 * - 获取计费统计信息
 * - 订阅购买/续费处理（monthly/yearly/ltd 统一逻辑）
 * - 订阅过期检查（懒惰执行）
 */
class BillingService extends BaseService
{
    /**
     * 获取配置前缀
     */
    private function getPrefix()
    {
        return get_option('billing_meta_prefix', 'app');
    }

    /**
     * 获取用户积分信息
     * 包含懒惰重置逻辑：自动检查并重置月度积分
     * 
     * @param int $user_id 用户ID
     * @return array ['credits' => int, 'tier' => string, 'isLtd' => bool, 'expiresAt' => string|null]
     */
    public function getCredits($user_id)
    {
        $prefix = $this->getPrefix();
        
        // 检查订阅是否过期
        $this->checkSubscriptionExpired($user_id);
        
        // 读取用户等级
        $tier = get_user_meta($user_id, "{$prefix}_tier", true) ?: 'free';
        
        // 读取积分
        $credits = (int) get_user_meta($user_id, "{$prefix}_credits", true);
        
        // 懒惰重置：检查是否需要重置月度积分
        if (in_array($tier, ['monthly', 'yearly', 'ltd'])) {
            $reset_at = (int) get_user_meta($user_id, "{$prefix}_reset_at", true);
            $now = time();
            
            if ($reset_at && $now >= $reset_at) {
                $monthly_credits = (int) get_option('billing_monthly_credits', 30);
                update_user_meta($user_id, "{$prefix}_credits", $monthly_credits);
                
                // 设置下次重置时间（下个月的同一天）
                $next_reset = strtotime('+1 month', $reset_at);
                update_user_meta($user_id, "{$prefix}_reset_at", $next_reset);
                
                $credits = $monthly_credits;
            }
        }
        
        // 读取订阅到期时间（monthly/yearly/ltd 均有值，ltd 为100年后）
        $expires_at = null;
        if (in_array($tier, ['monthly', 'yearly', 'ltd'])) {
            $expires_timestamp = get_user_meta($user_id, "{$prefix}_expires_at", true);
            if ($expires_timestamp) {
                $expires_at = date('c', $expires_timestamp);
            }
        }
        
        return [
            'credits' => $credits,
            'tier' => $tier,
            'isLtd' => $tier === 'ltd',
            'expiresAt' => $expires_at
        ];
    }

    /**
     * 消耗用户积分
     * 
     * @param int $user_id 用户ID
     * @param int $cost 消耗数量
     * @return array|WP_Error ['success' => bool, 'remainingCredits' => int] 或错误
     */
    public function consumeCredits($user_id, $cost)
    {
        // 消耗前先执行完整的积分获取，确保懒惰重置先触发
        $this->getCredits($user_id);
        
        $prefix = $this->getPrefix();
        
        // 获取当前积分（重置后的最新值）
        $current_credits = (int) get_user_meta($user_id, "{$prefix}_credits", true);
        
        // 检查余额
        if ($current_credits < $cost) {
            return new \WP_Error(
                'insufficient_credits',
                "积分不足，当前余额：{$current_credits}，需要：{$cost}",
                [
                    'status' => 400,
                    'current_credits' => $current_credits,
                    'required_credits' => $cost
                ]
            );
        }
        
        // 扣除积分
        $new_credits = $current_credits - $cost;
        update_user_meta($user_id, "{$prefix}_credits", $new_credits);
        
        return [
            'success' => true,
            'remainingCredits' => $new_credits,
            'message' => '积分消耗成功'
        ];
    }

    /**
     * 获取计费统计信息（公开接口）
     * 
     * @return array ['totalUsers' => int, 'ltdCount' => int]
     */
    public function getStats()
    {
        $prefix = $this->getPrefix();
        $cache_key = "{$prefix}_billing_stats";
        
        // 尝试读取缓存
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        // 统计总用户数
        $user_count_data = count_users();
        $total_users = $user_count_data['total_users'];
        
        // 统计LTD用户数
        $ltd_users = get_users([
            'meta_key' => "{$prefix}_tier",
            'meta_value' => 'ltd',
            'count_total' => true,
            'fields' => 'ID'
        ]);
        $ltd_count = count($ltd_users);
        
        $result = [
            'totalUsers' => (int) $total_users,
            'ltdCount' => (int) $ltd_count
        ];
        
        // 缓存60秒
        set_transient($cache_key, $result, 60);
        
        return $result;
    }

    /**
     * 检查订阅是否过期（懒惰检查）
     * 如果已过期，自动降级为free
     * 
     * @param int $user_id 用户ID
     * @return void
     */
    public function checkSubscriptionExpired($user_id)
    {
        $prefix = $this->getPrefix();
        $tier = get_user_meta($user_id, "{$prefix}_tier", true);
        
        // 仅检查订阅用户（ltd 有效期100年，实际不会触发）
        if (!in_array($tier, ['monthly', 'yearly', 'ltd'])) {
            return;
        }
        
        $expires_at = (int) get_user_meta($user_id, "{$prefix}_expires_at", true);
        
        // 如果已过期，降级为free
        if ($expires_at && $expires_at < time()) {
            delete_user_meta($user_id, "{$prefix}_tier");
            delete_user_meta($user_id, "{$prefix}_expires_at");
            delete_user_meta($user_id, "{$prefix}_reset_at");
            update_user_meta($user_id, "{$prefix}_credits", 0);
        }
    }

    /**
     * 订阅购买成功处理（统一处理 monthly/yearly/ltd）
     * 
     * LTD 简化设计：LTD = 100年的年费用户，按月重置 100 积分
     * 
     * @param int $user_id 用户ID
     * @param string $tier 用户等级：'monthly' | 'yearly' | 'ltd'
     * @param int $duration_months 订阅时长（月数）。monthly=1, yearly=12, ltd=1200
     * @return bool|WP_Error
     */
    public function handleSubscriptionPurchase($user_id, $tier, $duration_months = null)
    {
        if (!in_array($tier, ['monthly', 'yearly', 'ltd'])) {
            return new \WP_Error('invalid_plan', '无效的订阅类型');
        }
        
        // 默认时长：monthly=1个月, yearly=12个月, ltd=1200个月(100年)
        if ($duration_months === null) {
            $duration_months = match($tier) {
                'monthly' => 1,
                'yearly' => 12,
                'ltd' => 1200,
            };
        }
        
        $prefix = $this->getPrefix();
        $monthly_credits = (int) get_option('billing_monthly_credits', 30);
        
        // 设置用户等级
        update_user_meta($user_id, "{$prefix}_tier", $tier);
        
        // 充值积分（初次购买）
        update_user_meta($user_id, "{$prefix}_credits", $monthly_credits);
        
        // 设置下次重置时间（下个月）
        $reset_at = strtotime('+1 month');
        update_user_meta($user_id, "{$prefix}_reset_at", $reset_at);
        
        // 设置订阅到期时间
        $expires_at = strtotime("+{$duration_months} months");
        update_user_meta($user_id, "{$prefix}_expires_at", $expires_at);
        
        return true;
    }

    /**
     * 订阅续费处理
     * 
     * @param int $user_id 用户ID
     * @param string $tier 用户等级：'monthly' | 'yearly' | 'ltd'
     * @param int $duration_months 续费时长（月数）
     * @return bool|WP_Error
     */
    public function handleSubscriptionRenewal($user_id, $tier, $duration_months = null)
    {
        if (!in_array($tier, ['monthly', 'yearly', 'ltd'])) {
            return new \WP_Error('invalid_plan', '无效的订阅类型');
        }

        // 默认时长
        if ($duration_months === null) {
            $duration_months = match($tier) {
                'monthly' => 1,
                'yearly' => 12,
                'ltd' => 1200,
            };
        }
        
        $prefix = $this->getPrefix();
        $monthly_credits = (int) get_option('billing_monthly_credits', 30);
        
        // 充值积分（续费直接设置为满额）
        update_user_meta($user_id, "{$prefix}_credits", $monthly_credits);
        
        // 延长到期时间
        $current_expires = (int) get_user_meta($user_id, "{$prefix}_expires_at", true);
        $base_time = max($current_expires, time());
        $new_expires = strtotime("+{$duration_months} months", $base_time);
        update_user_meta($user_id, "{$prefix}_expires_at", $new_expires);
        
        // 设置下次重置时间
        $reset_at = strtotime('+1 month', time());
        update_user_meta($user_id, "{$prefix}_reset_at", $reset_at);
        
        return true;
    }
}

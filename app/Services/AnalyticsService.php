<?php

/**
 * Analytics Service
 * 
 * 通用数据分析服务
 * 为所有项目提供统一的数据追踪、统计和分析功能
 * 
 * 功能：
 * - 浏览量追踪（Views）
 * - 点击量追踪（Clicks）
 * - 自定义事件追踪
 * - 数据统计和报表
 * - 转化率计算
 * 
 * 使用示例：
 * ```php
 * // 追踪浏览
 * theme('analytics')->trackView('post', $post_id);
 * theme('analytics')->trackView('site', $site_id);
 * theme('analytics')->trackView('donation', $donation_id);
 * 
 * // 追踪点击
 * theme('analytics')->trackClick('post', $post_id);
 * 
 * // 获取统计数据
 * $stats = theme('analytics')->getAnalytics('post', $post_id);
 * ```
 */

namespace App\Services;

use App\Traits\TimeTrait;

class AnalyticsService extends BaseService
{
    use TimeTrait;

    /**
     * 追踪浏览量（混合存储策略）
     * 
     * 更新多个维度的数据：
     * - 总浏览量
     * - 今日浏览量
     * - 本周浏览量
     * - 本月浏览量
     * - 每日详细数据（JSON）
     * - 最后浏览时间
     * 
     * @param string $post_type 文章类型 (post, site, donation, etc.)
     * @param int $post_id 文章ID
     * @return bool 是否成功
     */
    public function trackView($post_type, $post_id)
    {
        if (!$post_id || !get_post($post_id)) {
            return false;
        }

        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        // 1. 更新总浏览量
        $this->incrementMeta($post_id, $post_type . '_views');
        
        // 2. 更新今日浏览量（带日期检查）
        $this->updateDailyCount($post_id, $post_type . '_views_today', $today);
        
        // 3. 更新本周浏览量（带周检查）
        $this->updatePeriodCount($post_id, $post_type . '_views_week', $week_start, 'week');
        
        // 4. 更新本月浏览量（带月检查）
        $this->updatePeriodCount($post_id, $post_type . '_views_month', $month_start, 'month');
        
        // 5. 更新90天历史数据（混合存储策略）
        $this->updateDailyHistory($post_id, $post_type . '_views_daily', $today);
        
        // 6. 更新最后浏览时间
        update_post_meta($post_id, $post_type . '_last_viewed', current_time('mysql'));
        
        return true;
    }
    
    /**
     * 追踪点击事件（混合存储策略）
     * 
     * 更新多个维度的数据：
     * - 总点击量
     * - 今日点击量
     * - 本周点击量
     * - 本月点击量
     * - 每日详细数据（JSON）
     * 
     * @param string $post_type 文章类型
     * @param int $post_id 文章ID
     * @return bool 是否成功
     */
    public function trackClick($post_type, $post_id)
    {
        if (!$post_id || !get_post($post_id)) {
            return false;
        }

        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        // 1. 更新总点击量
        $this->incrementMeta($post_id, $post_type . '_clicks');
        
        // 2. 更新今日点击量
        $this->updateDailyCount($post_id, $post_type . '_clicks_today', $today);
        
        // 3. 更新本周点击量
        $this->updatePeriodCount($post_id, $post_type . '_clicks_week', $week_start, 'week');
        
        // 4. 更新本月点击量
        $this->updatePeriodCount($post_id, $post_type . '_clicks_month', $month_start, 'month');
        
        // 5. 更新每日详细数据
        $this->updateDailyHistory($post_id, $post_type . '_clicks_daily', $today);
        
        return true;
    }
    
    /**
     * 获取文章的分析数据（包含所有维度）
     * 
     * @param string $post_type 文章类型
     * @param int $post_id 文章ID
     * @return array 分析数据
     */
    public function getAnalytics($post_type, $post_id)
    {
        $views_total = (int) get_post_meta($post_id, $post_type . '_views', true);
        $clicks_total = (int) get_post_meta($post_id, $post_type . '_clicks', true);
        
        $views_today = (int) get_post_meta($post_id, $post_type . '_views_today', true);
        $views_week = (int) get_post_meta($post_id, $post_type . '_views_week', true);
        $views_month = (int) get_post_meta($post_id, $post_type . '_views_month', true);
        
        $clicks_today = (int) get_post_meta($post_id, $post_type . '_clicks_today', true);
        $clicks_week = (int) get_post_meta($post_id, $post_type . '_clicks_week', true);
        $clicks_month = (int) get_post_meta($post_id, $post_type . '_clicks_month', true);
        
        return [
            'views' => $views_total,
            'clicks' => $clicks_total,
            'views_today' => $views_today,
            'views_week' => $views_week,
            'views_month' => $views_month,
            'clicks_today' => $clicks_today,
            'clicks_week' => $clicks_week,
            'clicks_month' => $clicks_month,
            'conversion_rate' => $this->calculateConversionRate($views_total, $clicks_total),
            'conversion_rate_week' => $this->calculateConversionRate($views_week, $clicks_week),
            'conversion_rate_month' => $this->calculateConversionRate($views_month, $clicks_month),
            'last_viewed' => get_post_meta($post_id, $post_type . '_last_viewed', true),
        ];
    }

    /**
     * 追踪自定义事件
     * 
     * @param string $event_name 事件名称
     * @param array $data 事件数据
     * @return bool 是否成功
     */
    public function trackEvent($event_name, $data = [])
    {
        return $this->logEvent($event_name, array_merge($data, [
            'ip' => $this->getClientIp(),
            'timestamp' => current_time('mysql'),
        ]));
    }

    /**
     * 批量获取多个文章的统计数据
     * 
     * @param string $post_type 文章类型
     * @param array $post_ids 文章ID数组
     * @return array 统计数据数组
     */
    public function getBatchAnalytics($post_type, $post_ids)
    {
        $results = [];
        
        foreach ($post_ids as $post_id) {
            $results[$post_id] = $this->getAnalytics($post_type, $post_id);
        }
        
        return $results;
    }

    /**
     * 获取统计概览
     * 
     * @param array $filters 过滤条件
     * @return array 统计概览
     */
    public function getStats($filters = [])
    {
        $post_type = $filters['post_type'] ?? 'post';
        $date_range = $filters['date_range'] ?? 7; // 默认7天
        
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
        ];
        
        // 日期范围过滤
        if ($date_range) {
            $args['date_query'] = [
                [
                    'after' => date('Y-m-d', strtotime("-{$date_range} days")),
                ],
            ];
        }
        
        $post_ids = get_posts($args);
        
        $total_views = 0;
        $total_clicks = 0;
        
        foreach ($post_ids as $post_id) {
            $total_views += (int) get_post_meta($post_id, $post_type . '_views', true);
            $total_clicks += (int) get_post_meta($post_id, $post_type . '_clicks', true);
        }
        
        return [
            'total_posts' => count($post_ids),
            'total_views' => $total_views,
            'total_clicks' => $total_clicks,
            'avg_views' => count($post_ids) > 0 ? round($total_views / count($post_ids), 2) : 0,
            'avg_clicks' => count($post_ids) > 0 ? round($total_clicks / count($post_ids), 2) : 0,
            'overall_conversion_rate' => $this->calculateConversionRate($total_views, $total_clicks),
            'date_range' => $date_range,
        ];
    }

    /**
     * 获取热门内容（按浏览量）
     * 
     * @param string $post_type 文章类型
     * @param int $limit 数量限制
     * @param int $days 时间范围（天）
     * @return array 热门内容列表
     */
    public function getTopByViews($post_type = 'post', $limit = 10, $days = 30)
    {
        global $wpdb;
        
        $meta_key = $post_type . '_views';
        
        $query = "
            SELECT p.ID, p.post_title, pm.meta_value as views
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            AND p.post_date > DATE_SUB(NOW(), INTERVAL %d DAY)
            ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $post_type, $meta_key, $days, $limit));
    }

    /**
     * 获取指定日期范围的趋势数据
     * 
     * @param string $post_type 文章类型
     * @param int $post_id 文章ID
     * @param int $days 天数（1-90）
     * @param string $metric 指标类型 (views/clicks)
     * @return array 趋势数据
     */
    public function getTrend($post_type, $post_id, $days = 7, $metric = 'views')
    {
        $meta_key = $post_type . '_' . $metric . '_daily';
        $daily_data = get_post_meta($post_id, $meta_key, true);
        
        if (!$daily_data) {
            return [];
        }
        
        $data = json_decode($daily_data, true);
        if (!is_array($data)) {
            return [];
        }
        
        // 获取最近N天的数据
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $result[$date] = isset($data[$date]) ? (int) $data[$date] : 0;
        }
        
        return $result;
    }

    /**
     * 获取热门内容（本周/本月/总计）
     * 
     * @param string $post_type 文章类型
     * @param int $limit 数量限制
     * @param string $period 时间周期 (week/month/total)
     * @return array 热门内容列表
     */
    public function getTopContent($post_type = 'post', $limit = 10, $period = 'week')
    {
        $meta_key = $post_type . '_views';
        
        if ($period === 'week') {
            $meta_key = $post_type . '_views_week';
        } elseif ($period === 'month') {
            $meta_key = $post_type . '_views_month';
        }
        
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_key' => $meta_key,
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => $meta_key,
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ],
            ],
        ];
        
        $query = new \WP_Query($args);
        $results = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $results[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'views' => (int) get_post_meta($post_id, $meta_key, true),
                    'url' => get_permalink(),
                ];
            }
            wp_reset_postdata();
        }
        
        return $results;
    }

    /**
     * 重置文章的统计数据
     * 
     * @param int $post_id 文章ID
     * @param string $post_type 文章类型
     * @return bool 是否成功
     */
    public function resetAnalytics($post_id, $post_type = 'post')
    {
        $meta_keys = [
            $post_type . '_views',
            $post_type . '_clicks',
            $post_type . '_views_today',
            $post_type . '_views_week',
            $post_type . '_views_month',
            $post_type . '_clicks_today',
            $post_type . '_clicks_week',
            $post_type . '_clicks_month',
            $post_type . '_daily_history',
            $post_type . '_clicks_daily',
            $post_type . '_last_viewed',
        ];
        
        foreach ($meta_keys as $key) {
            delete_post_meta($post_id, $key);
        }
        
        return true;
    }

    /**
     * 递增 Meta 字段值
     * 
     * @param int $post_id 文章ID
     * @param string $meta_key Meta 键名
     * @return bool 是否成功
     */
    protected function incrementMeta($post_id, $meta_key)
    {
        $current = (int) get_post_meta($post_id, $meta_key, true);
        return update_post_meta($post_id, $meta_key, $current + 1);
    }

    /**
     * 更新今日计数（带日期检查）
     * 
     * @param int $post_id 文章ID
     * @param string $meta_key Meta 键名
     * @param string $today 今天日期
     * @return bool 是否成功
     */
    protected function updateDailyCount($post_id, $meta_key, $today)
    {
        $date_key = $meta_key . '_date';
        $stored_date = get_post_meta($post_id, $date_key, true);
        
        if ($stored_date !== $today) {
            // 新的一天，重置计数
            update_post_meta($post_id, $date_key, $today);
            update_post_meta($post_id, $meta_key, 1);
        } else {
            // 同一天，递增
            $this->incrementMeta($post_id, $meta_key);
        }
        
        return true;
    }

    /**
     * 更新周期计数（周/月）
     * 
     * @param int $post_id 文章ID
     * @param string $meta_key Meta 键名
     * @param string $period_start 周期开始日期
     * @param string $period_type 周期类型 (week/month)
     * @return bool 是否成功
     */
    protected function updatePeriodCount($post_id, $meta_key, $period_start, $period_type)
    {
        $date_key = $meta_key . '_start';
        $stored_start = get_post_meta($post_id, $date_key, true);
        
        if ($stored_start !== $period_start) {
            // 新的周期，重置计数
            update_post_meta($post_id, $date_key, $period_start);
            update_post_meta($post_id, $meta_key, 1);
        } else {
            // 同一周期，递增
            $this->incrementMeta($post_id, $meta_key);
        }
        
        return true;
    }

    /**
     * 更新每日历史数据（JSON 存储，保留90天）
     * 
     * @param int $post_id 文章ID
     * @param string $meta_key Meta 键名
     * @param string $today 今天日期
     * @return bool 是否成功
     */
    protected function updateDailyHistory($post_id, $meta_key, $today)
    {
        $history = get_post_meta($post_id, $meta_key, true);
        $data = $history ? json_decode($history, true) : [];
        
        if (!is_array($data)) {
            $data = [];
        }
        
        // 递增今日计数
        if (!isset($data[$today])) {
            $data[$today] = 0;
        }
        $data[$today]++;
        
        // 只保留最近90天的数据
        $cutoff_date = date('Y-m-d', strtotime('-90 days'));
        foreach ($data as $date => $count) {
            if ($date < $cutoff_date) {
                unset($data[$date]);
            }
        }
        
        // 保存 JSON
        return update_post_meta($post_id, $meta_key, wp_json_encode($data));
    }

    /**
     * 计算转化率
     * 
     * @param int $views 浏览量
     * @param int $clicks 点击量
     * @return float 转化率（百分比）
     */
    protected function calculateConversionRate($views, $clicks)
    {
        if ($views == 0) {
            return 0;
        }
        
        return round(($clicks / $views) * 100, 2);
    }

    /**
     * 记录事件日志
     * 
     * @param string $event_type 事件类型
     * @param array $data 事件数据
     * @return bool 是否成功
     */
    protected function logEvent($event_type, $data)
    {
        if (function_exists('theme') && theme('log')) {
            theme('log')->log("Analytics: {$event_type}", $data);
        }
        
        // 未来可以扩展：保存到自定义数据表、发送到外部分析平台等
        
        return true;
    }

    /**
     * 获取客户端IP地址
     * 
     * @return string IP地址
     */
    protected function getClientIp()
    {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            // Nginx
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Proxy
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return sanitize_text_field($ip);
    }
}

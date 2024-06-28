<?php

namespace App\Services;

class StatService extends BaseService
{
    protected $viewsMetaKey = 'views';
    protected $incomeMetaKey = 'income';
    protected $supportersMetaKey = 'supporters';

    /**
     * 计算用户总收入
     *
     * @return number or 0 if no items found.
     */
    public function calcTotalIncome($uid)
    {
        $total = 0;
        $args = array(
            'meta_key' => 'amount',
            'post_type' => array('donation'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'to',
                    'value' => $uid,
                    'compare' => '=',
                ),
            ),
        );
        $ids = get_posts($args);

        foreach ($ids as $id) {
            $total += (int) get_post_meta($id, 'amount', true);
        }

        return (int) $total;
    }

    /**
     * 获取用户总收入
     *
     * @return number or 0 if no items found.
     */
    public function getTotalIncome($uid)
    {
        $metaKey = $this->incomeMetaKey;

        $value = (int) get_user_meta($uid, $metaKey, true);

        return $value ? $value : 0;
    }

    /**
     * 保存总收入
     */
    public function setTotalIncome($uid, $value)
    {
        $metaKey = $this->incomeMetaKey;

        update_user_meta($uid, $metaKey, $value);
    }

    /**
     * 计算赞助总人数（先使用post_count代替）
     * 
     * 当前只统计donation，shop等没有计入
     *
     * @return number or 0 if no items found.
     */
    public function calcTotalSupporters($uid)
    {
        $total = 0;
        $args = array(
            'meta_key' => 'amount',
            'post_type' => array('donation'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'to',
                    'value' => $uid,
                    'compare' => '=',
                ),
            ),
        );
        $ids = get_posts($args);

        $total = count($ids);

        return (int) $total;
    }

    /**
     * 获取赞助总人数
     *
     * @return number or 0 if no items found.
     */
    public function getTotalSupporters($uid)
    {
        $metaKey = $this->supportersMetaKey;

        $value = (int) get_user_meta($uid, $metaKey, true);

        return $value ? $value : 0;
    }

    /**
     * 保存赞助总人数
     */
    public function setTotalSupporters($uid, $value)
    {
        $metaKey = $this->supportersMetaKey;

        update_user_meta($uid, $metaKey, $value);
    }

    /**
     * 获取文章访问次数
     *
     * @return number or 0 if no items found.
     */
    public function getPostViews($id)
    {
        $metaKey = $this->viewsMetaKey;

        $value = (int) get_post_meta($id, $metaKey, true);

        return $value ? $value : 0;
    }

    /**
     * 统计文章访问次数（自动+1，修正空值）
     */
    public function setPostViews($id)
    {
        $metaKey = $this->viewsMetaKey;

        $count = $this->getPostViews($id);

        if ($count == '') {
            $count = 0;
            delete_post_meta($id, $metaKey);
            add_post_meta($id, $metaKey, 0);
        } else {
            $count++;
            update_post_meta($id, $metaKey, $count);
        }
    }

    /**
     * 获取用户主页访问次数
     *
     * @return number or 0 if no items found.
     */
    public function getUserViews($uid)
    {
        $metaKey = $this->viewsMetaKey;

        $value = (int) get_user_meta($uid, $metaKey, true);

        return $value ? $value : 0;
    }

    /**
     * 统计用户主页访问次数（自动+1，修正空值）
     *
     * @return number or 0 if meta not exist
     */
    public function setUserViews($uid)
    {
        $metaKey = $this->viewsMetaKey;

        $count = $this->getUserViews($uid);

        if ($count == '') {
            $count = 0;
            delete_user_meta($uid, $metaKey);
            add_user_meta($uid, $metaKey, 0);
        } else {
            $count++;
            update_user_meta($uid, $metaKey, $count);
        }

        return $count;
    }

    /**
     * 更新contribution的值，可增可减
     */
    public function updateUserContribution($user_id, $creator_id, $amount)
    {
        $meta_value = get_user_meta($creator_id, 'contribution', true);
        // just assign this key a new value
        $meta_value[$user_id] += $amount;
        // Then just save it again.
        update_user_meta($creator_id, 'contribution', $meta_value);
    }

    /**
     * 获取A打赏给B的总金额
     *
     * @param   user_id    普通用户ID
     * @param   creator_id 创作者ID
     */
    public function getUserContribution($user_id, $creator_id)
    {
        $contribution = get_user_meta($creator_id, 'contribution', true);

        return (int) $contribution[$user_id];
    }

    /**
     * 重新计算全部统计结果
     *
     * @return 数据对象 or 0 if meta not exist
     */
    public function refresh($uid)
    {
        // 总收入
        $total_income = $this->calcTotalIncome($uid);
        $this->setTotalIncome($uid, $total_income);

        // 赞助总人数
        $total_supporters = $this->calcTotalSupporters($uid);
        $this->setTotalSupporters($uid, $total_supporters);

        // 主页访问次数，是自动实时统计的，不需要计算
        $total_views = $this->getUserViews($uid);

        // 准备返回的数据
        $result = [
            'income' => $total_income,
            'supporters' => $total_supporters,
            'views' => $total_views,
        ];

        return $result;
    }
}

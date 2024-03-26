<?php

namespace App\Services;

class StatService extends BaseService
{
    protected $viewsKey = 'views';

    /**
     * 获取用户总收入
     *
     * @return number or 0 if no items found.
     */
    public function getTotalIncome($uid)
    {
        $name = $uid . '_total_income';

        $value = get_transient($name);

        // 当缓存不存在时，我们计算值
        if (!$value) {
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

            $value = $total;

            set_transient($name, $value);
        }

        return (int) $value;
    }

    /**
     * 获取赞助总人数（先使用post_count代替）
     *
     * @return number or 0 if no items found.
     */
    public function getTotalSupporters($uid)
    {
        $name = $uid . '_total_supporters';

        $value = get_transient($name);

        // 当缓存不存在时，我们计算值
        if (!$value) {
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

            $value = count($ids);

            set_transient($name, $value);
        }

        return (int) $value;
    }

    /**
     * 统计文章访问次数
     */
    public function setPostViews($postID)
    {
        $countKey = $this->viewsKey;
        $count = get_post_meta($postID, $countKey, true);
        if ($count == '') {
            $count = 0;
            delete_post_meta($postID, $countKey);
            add_post_meta($postID, $countKey, 0);
        } else {
            $count++;
            update_post_meta($postID, $countKey, $count);
        }
    }

    /**
     * 统计用户主页访问次数
     */
    public function setUserViews($user_id)
    {
        $countKey = $this->viewsKey;
        $count = get_user_meta($user_id, $countKey, true);
        if ($count == '') {
            $count = 0;
            delete_user_meta($user_id, $countKey);
            add_user_meta($user_id, $countKey, 0);
        } else {
            $count++;
            update_user_meta($user_id, $countKey, $count);
        }
    }

    /**
     * 获取用户主页访问次数
     *
     * @return number or 0 if no items found.
     */
    public function getPostViews($uid)
    {
        $countKey = $this->viewsKey;

        $views = (int) get_post_meta($uid, $countKey, true);

        return $views;
    }

    /**
     * 获取用户主页访问次数
     *
     * @return number or 0 if no items found.
     */
    public function getUserViews($uid)
    {
        $countKey = $this->viewsKey;

        $views = (int) get_user_meta($uid, $countKey, true);

        return $views;
    }
}

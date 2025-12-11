<?php

namespace App\Projects\Fans\Services;

use App\Services\UserService;
use function Tonik\Theme\App\theme;

/**
 * Fans 项目用户服务扩展
 * 
 * 在基础用户服务基础上，添加 Fans 特定功能：
 * - 关注/取消关注
 * - 收款信息验证
 * - 打赏支持者验证
 */
class FansUserService extends UserService
{
    protected $followingKey = 'following'; // user_meta: following
    protected $realnameKey = 'realname'; // user_meta: realname
    protected $alipayKey = 'alipay'; // user_meta: alipay

    /**
     * 当前用户关注指定用户
     *
     * @return true or false if failed
     */
    public function follow($user_id)
    {
        $meta = $this->user_meta_push($this->current_user_id, $this->followingKey, $user_id);

        return $meta;
    }

    /**
     * 当前用户取消关注指定用户
     *
     * @return true or false if failed
     */
    public function unFollow($user_id)
    {
        $meta = $this->user_meta_pop($this->current_user_id, $this->followingKey, $user_id);

        return $meta;
    }

    /**
     * 获取当前用户的关注列表
     *
     * @return array or false if failed
     */
    public function getFollowing()
    {
        $meta = get_user_meta($this->current_user_id, $this->followingKey, true);

        return $meta ? $meta : [];
    }

    /**
     * 当前用户是否关注指定用户
     *
     * @return true or false
     */
    public function isFollowed($user_id)
    {
        $following = $this->getFollowing();

        theme('log')->debug('isFollowed', $following, $user_id);

        if (!is_array($following)) {
            theme('log')->error('$following is not array', $following, $user_id);
            return false;
        }

        return in_array($user_id, $following);
    }

    /**
     * 指定用户是否完成收款信息设置
     *
     * @return true or false
     */
    public function hasPayment($user_id)
    {
        $realname = get_user_meta($user_id, $this->realnameKey, true);
        $alipay = get_user_meta($user_id, $this->alipayKey, true);

        theme('log')->debug('hasPayment', $user_id, $realname, $alipay);

        if (empty($realname) || empty($alipay)) {
            return false;
        }

        return true;
    }

    /**
     * 指定用户是否有被打赏的记录
     *
     * @return true or false
     */
    public function hasSupporters($user_id)
    {
        $total_supporters = theme('stat')->calcTotalSupporters($user_id);

        theme('log')->debug('hasSupporters', $user_id, $total_supporters);

        return $total_supporters > 0;
    }
}

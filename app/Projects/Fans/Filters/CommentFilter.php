<?php

namespace App\Projects\Fans\Filters;

/**
 * 评论相关过滤器
 * 
 * 包含：
 * - REST 响应修改（添加作者信息）
 */
class CommentFilter
{
    /**
     * 注册所有评论相关过滤器
     */
    public static function register()
    {
        self::registerRestPrepareFilters();
    }

    /**
     * REST 响应修改过滤器
     */
    private static function registerRestPrepareFilters()
    {
        // 修改API中的comment对象
        add_filter('rest_prepare_comment', function ($response, $post, $request) {
            $_data = $response->data;

            // 获取用户数据
            $author_id = $_data['author'] ?? 0;
            if ($author_id) {
                $user = get_userdata($author_id);
                if ($user) {
                    $_data['author_slug'] = $user->user_nicename;
                }
            }

            // 获取parent用户数据
            $parent_id = $_data['parent'] ?? 0;
            $parent = $parent_id ? get_comment($parent_id) : null;
            if ($parent) {
                $_data['parent_name'] = $parent->comment_author;
            }

            $response->data = $_data;
            return $response;
        }, 10, 3);
    }
}

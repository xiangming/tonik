<?php

// define( 'CODE_MIN_INTERVAL', 60); // 验证码最小间隔（限制60s获取一次）
// define( 'CODE_EXPIRE_TIME', 1800); // 验证码30分钟有效

/**
 * 接口正确返回函数
 *
 * @param   $message  接口message
 * @param   $data     接口data，可选
 *
 * @return  标准接口json
 */
function resOK($message = 'success', $data = null)
{
    print_r(json_encode(['code'=>0, 'message'=>$message, 'data'=>$data]));
    return; 
}
function resError($message = 'error', $data = null)
{
    print_r(json_encode(['code'=>1, 'message'=>$message, 'data'=>$data]));
    return;
}


// /**
//  * 数组去空值 & 数组去key
//  * @author arvinxiang.com
//  * @since 1.0
//  */
// if ( !function_exists( 'arrayFilter' ) ) {
//     function arrayFilter($array) {
//         $array = array_filter($array);// 数组去空值
//         $array = array_values($array);// 数组去key

//         return $array;
//     }
// }

// /**
//  * 从api请求里面获取jwt-token
//  *
//  * @param   [type]  $request  api请求参数集合
//  *
//  * @return  [type]            [return description]
//  */
// function getJwtTokenFromRequest($request) {
//     $headers = $request->get_headers();
//     $token = substr($headers['authorization'][0], 7);
//     return $token;
// }

// /**
//  * 将缓存的手机号转正
//  *
//  * @param   [type]  $uid  [$uid description]
//  *
//  * @return  [type]        [return description]
//  */
// function updatePhoneFromPhoneTemp($uid) {
//     $phone_temp = get_user_meta( $uid, 'phone_temp', true );
//     update_user_meta( $uid, 'phone', $phone_temp );
//     delete_user_meta( $uid, 'phone_temp');
// }

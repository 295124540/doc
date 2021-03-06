<?php
namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Request;
use tools\HttpClient;


class ApiBaseController extends Controller
{
    protected $accessToken;
    protected $user;
    protected $userId;
    protected $loginAuth;

    protected function _initialize()
    {
        $this->accessToken = Request::instance()->header('access-token');

        if ($this->accessToken) {
            if (strlen($this->accessToken) < 24) {
                api(102, "access-token无效！");
            }
            $this->authenticate();//有token时，则验证登录
        } else {
            if ($this->loginAuth) {
                $this->beforeActionList['loginAuth'] = $this->loginAuth; //无token时，（自定义）验证登录\无需验证登录
            } else {
                $this->beforeActionList['loginAuth'] = null;//无token时，（默认）验证登录
            }
        }

    }

    /**
     * 登录
     * @param $user
     * @return bool
     */
    protected function doLogin($user)
    {
        $this->accessToken = $user['access_token'];
        $this->user = $user;
        $this->userId = $user['yunsu_id'];
        $rt = Cache::set($this->accessToken, $user, 10 * 24 * 60 * 60);//token有效期两个小时 调试时间为10天有效
        if (!$rt) die($rt);
        return true;
    }

    /**
     * 登录权限
     */
    protected function loginAuth()
    {
        if ($this->accessToken == null) {
            api(101, "该接口需要登录权限！");
        }
    }

    /**
     *  鉴定身份
     */
    protected function authenticate()
    {

        $loginUser = Cache::get($this->accessToken);
        //当本系统无登录时，去总用户系统认证(实现单点登录)
        if ($loginUser == null) {
            $params = config('thirdaccount.users_sys');
            $loginUser = $this->requestUserServer("/api/user/identityAuth", $params, false);
            $this->doLogin($loginUser);
        }

        if ($loginUser) {
            $this->user = $loginUser;
            $this->userId = $loginUser['yunsu_id'];
        }
    }

    /**
     * 重写 表单验证
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        $result = parent::validate($data, $validate, $message, $batch, $callback); // TODO: Change the autogenerated stub
        if (true !== $result) {
            error($result);
        }
    }

    /**
     * 请求用户系统
     * @param $path 服务器资源路径 例如：/api/user/login
     * @param null $params 参数
     * @param bool $isVerify 是否严格验证
     * @return mixed
     */
    protected function requestUserServer($path, $params = null, $isVerify = true)
    {
        $url = 'http://users.icloudinn.com' . $path;
        if ($this->accessToken) {
            if (strpos($url, '?')) {
                $url = $url . "&access-token=" . $this->accessToken;
            } else {
                $url = $url . "?access-token=" . $this->accessToken;
            }
        }

        if ($params) {
            $rt = HttpClient::post($url, $params);
        } else {
            $rt = HttpClient::get($url);
        }

        $info = json_decode($rt, true);

        if ($info) {
            if ($info['code'] == 100) {
                return $info['data'];
            } else {
                if ($isVerify) {
                    api($info['code'], "From Users System. " . $info['msg'], $info['data']);
                }
            }
        }
        die($rt);
    }
}

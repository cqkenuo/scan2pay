<?php
/**
 * Admin Controller
 */
Class AdminController extends Controller {

    public function actionLogin() {     //--{{{
        $pwd = $this->post('pwd', '');

        //是否被禁止访问
        $userIp = $this->getUserIp();
        if ($this->isUserLocked($userIp)) {
            throw new Exception("系统繁忙，请稍后再试！");
        }

        $errorMsg = '';
        if (!empty($pwd) && $pwd != USC::$app['config']['admpwd']) {
            $errorMsg = '登录密码错误，请检查大小写是否正确！';

            //Lock user ip for 30 days
            $this->lockUserIP($userIp);
        }else if (!empty($pwd) && $pwd == USC::$app['config']['admpwd']) {
            session_start();

            //save login time for login check
            $_SESSION['login_user'] = $this->getUserIp();

            return $this->redirect('/order/list/');
        }

        $pageTitle = "管理员登录";
        $viewName = 'login';
        $params = array(
            'errorMsg' => $errorMsg,
        );
        return $this->render($viewName, $params, $pageTitle);
    }       //--}}}

    //锁定用户ip地址，在有效期内禁止访问
    protected function lockUserIP($ip, $maxFailTime = 5, $expireDays = 30) {        //--{{{
        $blackIpDir = __DIR__ . '/../runtime/blackips/';
        if (!is_dir($blackIpDir)) {
            mkdir($blackIpDir);
        }

        $lockFile = "{$blackIpDir}{$ip}.lock";
        if (!file_exists($lockFile)) {
            return file_put_contents($lockFile, "1");
        }else {
            $failTime = file_get_contents($lockFile);
            if ($failTime > 0 && $failTime < $maxFailTime - 1) {
                return file_put_contents($lockFile, ($failTime+1));
            }
        }

        $expireTime = strtotime("+{$expireDays} days");
        $expireDate = date('Ymd', $expireTime);
        return file_put_contents($lockFile, "{$expireDate}");
    }       //--}}}

    //检查用户ip是否被封锁
    protected function isUserLocked($ip) {  //--{{{
        $isLocked = false;

        $blackIpDir = __DIR__ . '/../runtime/blackips/';
        $lockFile = "{$blackIpDir}{$ip}.lock";
        if (file_exists($lockFile)) {
            $expireDate = (int)file_get_contents($lockFile);
            $today = (int)date('Ymd');
            if ($expireDate > 0 && $today < $expireDate) {
                $isLocked = true;
            }
        }

        return $isLocked;
    }   //--}}}

}

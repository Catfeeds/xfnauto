<?php
/**
 * Created by PhpStorm.
 * User: aupl
 * Date: 2018-06-11
 * Time: 9:28
 */

namespace app\api\controller\v1\Shop;

use app\api\model\CustomerOrder;
use think\Controller;
use think\Db;
class Loan extends Base
{

    /**
     * 垫资申请列表
     * */
    public function index(){
        $result = sendMail('770517692@qq.com', '这是测试内容', 'jiangjun', '测试');
        dump($result);
    }

    /**
     * 垫资资格申请
     * */
    public function apply(){

    }

    /**
     * 添加垫资单
     * */
    public function create(){

    }

}
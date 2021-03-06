<?php
/**
 * Created by PhpStorm.
 * User: aupl
 * Date: 2018-05-31
 * Time: 11:46
 */

namespace app\api\controller\v3\Backend;

use Symfony\Component\Config\Definition\Exception\Exception;
use think\Controller;
use think\Db;
class Shop extends Admin
{

    /**
     * 店铺认证列表
     * @return json
     * */
    public function index(){
        $page  = isset($this->data['page']) && !empty($this->data['page']) ? $this->data['page'] + 0 : 1;
        $rows  = isset($this->data['rows']) && !empty($this->data['rows']) ? $this->data['rows'] + 0 : 50;

        $where = [];
        if(isset($this->data['state']) && $this->data['state'] != ''){
            $state = $this->data['state'] + 0;
            $where['si_state'] = $state;
        }

        if(isset($this->data['keywords']) && !empty($this->data['keywords'])){
            $keywords = htmlspecialchars(trim($this->data['keywords']));
            $where['si_shopName'] = ['like', '%' . $keywords . '%'];
        }
        
        $data = model('ShopInfo')->getShopInfoForPage($where, $page, $rows);
        $this->apiReturn(200, $data);
    }

    /**
     * 审核详情
     * */
    public function detail(){
        (!isset($this->data['id']) || empty($this->data['id'])) && $this->apiReturn(201, '', '参数非法');

        $id = $this->data['id'] + 0;
        $data = model('ShopInfo')->findOne(['si_id' => $id]);
        $this->apiReturn(200, $data ?: []);
    }

    /**
     * 审核
     * */
    public function verify(){
        (!isset($this->data['id']) || empty($this->data['id'])) && $this->apiReturn(201, '', '参数非法');
        (!isset($this->data['state']) || empty($this->data['state'])) && $this->apiReturn(201, '', '参数非法');

        $id    = $this->data['id'] + 0;
        $state = $this->data['state'] + 0;

        $reason = '';
        if($state == 2){
            (!isset($this->data['reason']) || empty($this->data['reason'])) && $this->apiReturn(201, '', '请输入拒绝原因');
            $reason = htmlspecialchars(trim($this->data['reason']));
        }

        $data = model('ShopInfo')->findOne(['si_id' => $id]);
        !$data && $this->apiReturn(201, '', '数据不存在');
        $data['state'] == 1 && $this->apiReturn(201, '', '审核已经通过，不能重复操作');
        $data['state'] == 2 && $this->apiReturn(201, '', '已拒绝该店铺通过审核');

        try{
            Db::startTrans();

            $infoData = [
                'si_operatorId' => $this->userId,
                'si_updateTime' => time(),
                'si_reason'     => $reason,
                'si_state'      => $state
            ];

            $result = Db::name('shop_info')->where(['si_id' => $id])->update($infoData);
            if($result === false){
                throw new Exception('更新状态失败');
            }

            $code = getRandomString(6);
            $orgData = [
                'parentId' => 1,
                'seq'      => 0,
                'orgCode'  => $code,
                'orgCodeLevel'   => 'XFN_0MHM37_' . $code,
                'shortName'      => $data['shopName'],
                'provinceId'     => $data['provinceId'],
                'provinceName'   => $data['provinceName'],
                'cityId'         => $data['cityId'],
                'cityName'       => $data['cityName'],
                'areaId'         => $data['areaId'],
                'areaName'       => $data['areaName'],
                'address'        => $data['address'],
                'linkman'        => $data['corporation'],
                'telephone'      => $data['phone'],
                'orgtype'        => 1,
                'orgLevel'       => 3,
                'status'         => 1,
                'imageurl'       => implode(',', $data['image']),
                'id_card_pic_on' => $data['idCardPicOn'],
                'id_card_pic_off'  => $data['idCardPicOff'],
                'nature_type'      => $data['type'],
                'business_license' => implode(',', $data['license']),
                'create_date'      => date('Y-m-d H:i:s'),
            ];

            $result = Db::name('system_organization')->insert($orgData);
            if(!$result){
                throw new Exception('新增门店失败');
            }

            $orgId = Db::name('system_organization')->getLastInsID();
            $user = [
                'user_type' => 2,
                'org_id'    => $orgId,
                'org_name'  => $data['shopName']
            ];

            $result = Db::name('shop_user')->where(['shop_user_id' => $data['userId']])->update($user);
            if($result === false){
                throw new Exception('更新用户信息失败');
            }

            Db::commit();
            $this->apiReturn(200);
        }catch (Exception $e){
            Db::rollback();
            $this->apiReturn(201);
        }
    }


}
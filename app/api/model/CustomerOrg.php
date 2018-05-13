<?php

/**
 * Created by PhpStorm.
 * User: liaozijie
 * Date: 2018-04-25
 * Time: 9:47
 */
namespace app\api\model;

use think\Db;
use think\Model;

class CustomerOrg extends Model
{

    protected $table = 'customer_customerorg';

    /*
     * 客户统计
     * */
    public function customerCount($userId, $orgId, $isIntensity = false, $isRole = false){
        $where = [
            'time_of_appointment_date' => ['between', [date('Y-m-01'), date('Y-m-t 23:59:59')]],
        ];

        $group = model('SystemUser')->getUserGroupInfo($userId);
        if($group['over_manage'] == 1){
            $where['org_id']         = $group['orgId'];
        }else{
            $where['system_user_id'] = $userId;
        }

        if($isIntensity){
            $where['intensity'] = '高';
        }

        return Db::name($this->table)->where($where)->count();
    }

    /*
     * 客户统计
     * */
    public function customerAppointmentCount($userId, $orgId, $isRole = false){
        $where = [
            'appointment_date' => ['between', [date('Y-m-d'), date('Y-m-d 23:59:59')]],
            'org_id' => $orgId
        ];

        if(!$isRole){
            $where['system_user_id'] = $userId;
        }

        return Db::name($this->table)->where($where)->count();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: liaozijie
 * Date: 2018-04-25
 * Time: 9:28
 */

namespace app\api_v2\controller\v3\Backend;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Controller;
use think\Db;
class ConsumerOrder extends Admin
{

    /**
     * 首页
     * @return json
     * */
    public function index(){
        $page  = isset($this->data['page']) && !empty($this->data['page']) ? $this->data['page'] + 0 : 1;
        $rows  = isset($this->data['rows']) && !empty($this->data['rows']) ? $this->data['rows'] + 0 : 50;


        $where = [
            'co.state'   => ['not in', [-1]],
            'co.is_del'  => 0
        ];

        if(isset($this->data['keywords'])&& !empty($this->data['keywords'])){
            $keywords= htmlspecialchars(trim($this->data['keywords']));
            $orderUser    = Db::name('consumer_order_user')->where(['user_name' => ['like', '%' . $keywords . '%']])->field('order_id')->select();
            $orderUserIds = array();
            if($orderUser){
                $orderUserIds = array_column($orderUser, 'order_id');
            }
            $join = [
                ['consumer_order_car oc', 'oc.stock_car_id=sc.stock_car_id', 'left'],
                ['consumer_order_info oi', 'oi.id=oc.info_id', 'left'],
            ];
            $stockCar = Db::name('stock_car sc')->where(['frame_number' => ['like', '%' . $keywords . '%']])->join($join)->field('oi.order_id')->select();
            $stockCarIds = array();
            if($stockCar){
                $stockCarIds = array_column($stockCar, 'order_id');

            }
            $orderIds = array_merge($orderUserIds, $stockCarIds);
            $where['co.id'] = ['in', $orderIds];
        }

        if(isset($this->data['orgId'])&& !empty($this->data['orgId'])){
            $orgId = $this->data['orgId'] + 0;
            $where['co.org_id'] = $orgId;
        }

        if(isset($this->data['state']) && !empty($this->data['state'])){
            $state = $this->data['state'] + 0;
            $where['co.state'] = $state;
        }

        if(!$this->isAdmin){//如果不是超级管理员，则显示自己及下级的数据
            $where['co.creator_id'] = ['in', $this->userIds];
        }

        $startTime = isset($this->data['startDate']) && !empty($this->data['startDate']) ? $this->data['startDate'] : '';
        $endTime   = isset($this->data['endDate'])   && !empty($this->data['endDate'])   ? $this->data['endDate'] : '';
        if($startTime && !$endTime){
            $where['co.create_time'] = ['egt', $startTime];
        }elseif(!$startTime && $endTime){
            $where['co.create_time'] = ['elt', $endTime];
        }else{
            $now = date('Y-m-d H:i:s');
            if($startTime == $endTime && $endTime <= $now){
                $where['co.create_time'] = ['egt', $startTime];
            }elseif($startTime == $endTime && $endTime >= $now){
                $where['co.create_time'] = ['elt', $startTime];
            }else{
                if($startTime > $endTime){
                    $where['co.create_time'] = ['between', [$endTime, $startTime]];
                }else{
                    $where['co.create_time'] = ['between', [$startTime, $endTime]];
                }
            }
        }

        $data = model('v2.ConsumerOrder')->getOrderListAll($where, $page, $rows);
        $this->apiReturn(200, ['list' => $data['list'], 'page' => $page, 'rows' => $rows, 'total' => $data['count']]);
    }

    /**
     * 资源订单详情
     * */
    public function consumerDetail(){
        (!isset($this->data['id']) || empty($this->data['id'])) && $this->apiReturn(201, '', '参数非法');

        $orderId = $this->data['id'] + 0;
        $data    = model('v2.ConsumerOrder')->getOrderDetailByOrderId($orderId);
        $this->apiReturn(200, $data);
    }

    public function export(){
        $where = [
            'co.state'   => ['not in', [-1]],
            'co.is_del'  => 0
        ];

        if(isset($this->data['keywords'])&& !empty($this->data['keywords'])){
            $keywords     = htmlspecialchars(trim($this->data['keywords']));
            $orderUser    = Db::name('consumer_order_user')->where(['user_name' => ['like', '%' . $keywords . '%']])->field('order_id')->select();
            $orderUserIds = array();
            if($orderUser){
                $orderUserIds = array_column($orderUser, 'order_id');
            }
            $join = [
                ['consumer_order_car oc', 'oc.stock_car_id=sc.stock_car_id', 'left'],
                ['consumer_order_info oi', 'oi.id=oc.info_id', 'left'],
            ];
            $stockCar = Db::name('stock_car sc')->where(['frame_number' => ['like', '%' . $keywords . '%']])->join($join)->field('oi.order_id')->select();
            $stockCarIds = array();
            if($stockCar){
                $stockCarIds = array_column($stockCar, 'order_id');

            }
            $orderIds = array_merge($orderUserIds, $stockCarIds);
            $where['co.id'] = ['in', $orderIds];
        }

        if(isset($this->data['orgId'])&& !empty($this->data['orgId'])){
            $orgId = $this->data['orgId'] + 0;
            $where['co.org_id'] = $orgId;
        }

        if(isset($this->data['state']) && !empty($this->data['state'])){
            $state = $this->data['state'] + 0;
            $where['co.state'] = $state;
        }

        if(!$this->isAdmin){//如果不是超级管理员，则显示自己及下级的数据
            $where['co.creator_id'] = ['in', $this->userIds];
        }

        $startTime = isset($this->data['startDate']) && !empty($this->data['startDate']) ? $this->data['startDate'] : '';
        $endTime   = isset($this->data['endDate'])   && !empty($this->data['endDate'])   ? $this->data['endDate'] : '';
        if($startTime && !$endTime){
            $where['co.create_time'] = ['egt', $startTime];
        }elseif(!$startTime && $endTime){
            $where['co.create_time'] = ['elt', $endTime];
        }else{
            $now = date('Y-m-d H:i:s');
            if($startTime == $endTime && $endTime <= $now){
                $where['co.create_time'] = ['egt', $startTime];
            }elseif($startTime == $endTime && $endTime >= $now){
                $where['co.create_time'] = ['elt', $startTime];
            }else{
                if($startTime > $endTime){
                    $where['co.create_time'] = ['between', [$endTime, $startTime]];
                }else{
                    $where['co.create_time'] = ['between', [$startTime, $endTime]];
                }
            }
        }

        $state = [
            '1' => '新建',
            '5' => '待收定金',
            '10' => '待配车',
            '15' => '待验车',
            '20' => '换车申请',
            '25' => '待换车',
            '30' => '待协商',
            '35' => '待收尾款',
            '37' => '已退款',
            '40' => '待出库',
            '45' => '待上传票证',
            '50' => '完成',
        ];

        $field = 'co.id as id,co.order_code as orderId,co.state as orderState,org_name as orgName,order_type as orderType,co.freight,creator,co.create_time as createTime,countermand_apply as countermandApply';
        $data  = Db::name('consumer_order co')->where($where)->field($field)->order('co.create_time desc')->select();
        if($data){
            foreach($data as $key => &$value){
                $orderId = $value['id'];
                $value['orgName'] = trim($value['orgName']);
                $value['totalDepositPrice'] = Db::name('consumer_order_info')->where(['order_id' => $orderId])->sum('deposit_price');
                $value['totalFinalPrice']   = 0;
                $value['totalRestPrice']    = 0;
                $value['orderType']         = $value['orderType'] == 1 ? '常规单' : '炒车单';
                $value['orderState']        = $value['countermandApply'] ? ($value['countermandApply'] == 1 ? '申请退款中' : '已退款') : $state[$value['orderState']];
                $orderInfo = Db::name('consumer_order_info')->where(['order_id' => $orderId])->field('naked_price,traffic_compulsory_insurance_price,commercial_insurance_price,car_num')->select();
                if($orderInfo){
                    $total = 0;
                    foreach($orderInfo as $vo){
                        $total += ($vo['naked_price'] + $vo['traffic_compulsory_insurance_price'] + $vo['commercial_insurance_price']) * $vo['car_num'];
                    }
                    $value['totalFinalPrice']   = $total + $value['freight'];
                    $value['totalRestPrice']    = $value['totalFinalPrice'] - $value['totalDepositPrice'];
                }

                $orderUserField      = 'id,order_id as orderId,user_name as userName,user_phone as userPhone';
                $value['customers']  = Db::name('consumer_order_user')->where(['order_id' => $orderId, 'type' => 1])->field($orderUserField)->select();
                if($value['customers']){
                    foreach($value['customers'] as $key => &$val){
                        $val['userName']  = $val['userName'] ?: '--';
                        $val['userPhone'] = $val['userPhone'] ?: '--';
                        $join = [
                            ['consumer_order_car oc', 'oc.info_id=oi.id', 'left'],
                            ['stock_car sc', 'sc.stock_car_id=oc.stock_car_id', 'left'],
                        ];
                        $val['infos'] = Db::name('consumer_order_info oi')->field('oi.id,sc.frame_number')->join($join)->where(['oi.order_id' => $orderId, 'oi.customer_id' => $val['id']])->select();
                        $val['infos'] = $val['infos'] ? array_column($val['infos'], 'frame_number') : '--';
                        $val['infos'] = $val['infos'] ? implode("\n", $val['infos']) : '--';
                    }
                    $value['frameNumber'] = $value['customers'] ? array_column($value['customers'], 'infos') : '--';
                    $value['userName'] = $value['customers'] ? array_column($value['customers'], 'userName') : '--';
                    $value['userPhone'] = $value['customers'] ? array_column($value['customers'], 'userPhone') : '--';
                }
            }
        }

        !$data && $this->apiReturn(201, '', '暂无数据');

        $name = '资源列表导出';
        $objPHPExcel = new \PHPExcel();
        $allLetter   = range('A', 'Z');

        $objPHPExcel->getProperties()->setCreator($this->user['realName']);

        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $name);
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:M1');

        $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(45);
        $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(24);

        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', '订单号')
            ->setCellValue('B2', '订购门店')
            ->setCellValue('C2', '客户姓名')
            ->setCellValue('D2', '手机号码')
            ->setCellValue('E2', '车架号')
            ->setCellValue('F2', '成交价')
            ->setCellValue('G2', '定金')
            ->setCellValue('H2', '尾款')
            ->setCellValue('I2', '运费')
            ->setCellValue('J2', '建单员')
            ->setCellValue('K2', '建单日期')
            ->setCellValue('L2', '订单类型')
            ->setCellValue('M2', '订单状态');

        if($data){
            foreach($data as $k => $item){
                $num = $k + 3;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $num, $item['orderId'])
                    ->setCellValue('B' . $num, $item['orgName'])
                    ->setCellValue('C' . $num, implode("\n", $item['userName']))
                    ->setCellValue('D' . $num, implode("\n", $item['userPhone']))
                    ->setCellValue('E' . $num, implode("\n", $item['frameNumber']))
                    ->setCellValue('F' . $num, $item['totalFinalPrice'])
                    ->setCellValue('G' . $num, $item['totalDepositPrice'])
                    ->setCellValue('H' . $num, $item['totalRestPrice'])
                    ->setCellValue('I' . $num, $item['freight'])
                    ->setCellValue('J' . $num, $item['creator'])
                    ->setCellValue('K' . $num, $item['createTime'])
                    ->setCellValue('L' . $num, $item['orderType'])
                    ->setCellValue('M' . $num, $item['orderState']);
                $objPHPExcel->getActiveSheet()->getRowDimension($num)->setRowHeight(30);
            }
        }

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        for ($i = 2; $i <= 13; $i++) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($allLetter[$i])->setWidth(20);
        }

        $endCell = 'M' . (count($data) + 2);
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    'style'  => \PHPExcel_Style_Border::BORDER_THIN,
                    'color'  => array('argb' => 'FF000000'),
                ),
            ),  'alignment'  => array(
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PHPExcel_Style_Alignment::VERTICAL_CENTER
            ),
        );

        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A2:' . $endCell)->applyFromArray($styleArray);
        $objPHPExcel->getActiveSheet()->getStyle('A'.(count($data) + 4))
            ->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

        $objPHPExcel->getActiveSheet()->getStyle('A'.(count($data) + 7))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:M2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)->setWrapText(true);

        $objPHPExcel->getActiveSheet()->setTitle($name);
        $objPHPExcel->setActiveSheetIndex(0);

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($name . '.xlsx');
        if(file_exists($name . '.xlsx')){
            vendor('Qiniu.autoload');
            $auth  = new Auth(config('qiniu.accesskey'), config('qiniu.secretkey'));
            $token = $auth->uploadToken(config('qiniu.bucket'));

            $upload = new UploadManager();
            list($ret, $err) = $upload->putFile($token, md5($name . microtime(true)) . '.xlsx', $name . '.xlsx');
            if ($err !== null) {
                $this->apiReturn(201, ['state' => 'error', 'msg' => $err]);
            } else {
                //返回图片的完整URL
                unlink($name . '.xlsx');
                $this->apiReturn(200, ['state' => 'success', 'url' => 'https://' . config('qiniu.domain') . '/' . $ret['key']]);
            }
        }else{
            $this->apiReturn(201, '', '文件不存在');
        }
    }

}
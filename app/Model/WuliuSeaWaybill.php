<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Model;

/**
 * @property int $id
 * @property int $sail_schedule_id
 * @property string $number
 * @property string $case_number
 * @property string $qf_number
 * @property string $box
 * @property string $box_main_code
 * @property string $good_name
 * @property string $weight
 * @property string $ship_company_towing_fee
 * @property string $ship_company_towing_fee_desc
 * @property string $car_fee
 * @property string $car_other_fee
 * @property string $car_other_fee_desc
 * @property int $car_id
 * @property string $car_finished_date
 * @property int $receipt_status
 * @property int $poundbill_status
 * @property int $box_reporting_status
 * @property string $liaison
 * @property string $liaison_mobile
 * @property string $liaison_address
 * @property string $liaison_address_detail
 * @property string $liaison_remark
 * @property string $estimated_time
 * @property string $driver_name
 * @property string $driver_mobile_number
 * @property string $driver_id_card
 * @property int $fh_status
 * @property int $rush_status
 * @property int $tos
 * @property int $ship_company_bill_id
 * @property int $motorcade_bill_id
 * @property int $partner_id
 * @property string $partner_towing_fee
 * @property string $partner_overdue_fee
 * @property string $partner_stockpiling_fee
 * @property string $partner_huandan_fee
 * @property string $partner_thc_fee
 * @property string $partner_print_fee
 * @property string $partner_clean_fee
 * @property string $partner_other_fee
 * @property string $partner_other_fee_desc
 * @property int $partner_stay_pole
 * @property string $partner_remarks
 * @property int $partner_bill_id
 * @property int $self_bill_id
 * @property int $type
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|WuliuSailSchedule $sailSchedule
 * @property null|WuliuCar $car
 * @property null|WuliuPartner $partner
 * @property null|WuliuBill $shipCompanyBill
 * @property null|WuliuBill $motorcadeBill
 * @property null|WuliuBill $partnerBill
 * @property null|WuliuBill $selfBill
 */
class WuliuSeaWaybill extends BaseModel
{
    public const FH_STATUS_NO = 0;

    public const FH_STATUS_YES = 1;

    public const FH_STATUS_DEFAULT = 10;

    public const SHIP_COMPANY_DZ_STATUS_NO = 0;

    public const SHIP_COMPANY_DZ_STATUS_YES = 1;

    public const MOTORCADE_DZ_STATUS_NO = 0;

    public const MOTORCADE_DZ_STATUS_YES = 1;

    public const PARTNER_DZ_STATUS_NO = 0;

    public const PARTNER_DZ_STATUS_YES = 1;

    public const RECEIPT_STATUS_DEFAULT = 0;

    public const RECEIPT_STATUS_NOT_UPLOAD = 1;

    public const RECEIPT_STATUS_UPLOADED = 2;

    public const POUNDBILL_STATUS_DEFAULT = 0;

    public const POUNDBILL_STATUS_NOT_TAKEN = 1;

    public const POUNDBILL_STATUS_NOT_POSTED = 2;

    public const POUNDBILL_STATUS_POSTED = 3;

    public const BOX_REPORTING_STATUS_DEFAULT = 0;

    public const BOX_REPORTING_STATUS_NOT_EXEC = 10;

    public const BOX_REPORTING_STATUS_EXEC = 20;

    public const RUSH_STATUS_NO = 0;

    public const RUSH_STATUS_YES = 1;

    public const TOS_DEFAULT = 0;

    public const TOS_DAOMEN = 1;

    public const TOS_DAOGANG = 2;

    public const TOS_JIAMENDIAN = 3;

    public const TYPE_DEFAULT = 0;

    public const TYPE_JINKOU = 1;

    public const TYPE_CHUKOU = 2;

    public const STATUS_DEFAULT = 0;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_sea_waybill';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'sail_schedule_id', 'number', 'case_number', 'qf_number', 'box', 'box_main_code', 'good_name', 'weight', 'ship_company_towing_fee', 'ship_company_towing_fee_desc', 'car_fee', 'car_other_fee', 'car_other_fee_desc', 'car_id', 'car_finished_date', 'receipt_status', 'poundbill_status', 'box_reporting_status', 'liaison', 'liaison_mobile', 'liaison_address', 'liaison_address_detail', 'liaison_remark', 'estimated_time', 'driver_name', 'driver_mobile_number', 'driver_id_card', 'fh_status', 'rush_status', 'tos', 'ship_company_bill_id', 'motorcade_bill_id', 'partner_id', 'partner_towing_fee', 'partner_overdue_fee', 'partner_stockpiling_fee', 'partner_huandan_fee', 'partner_thc_fee', 'partner_print_fee', 'partner_clean_fee', 'partner_other_fee', 'partner_other_fee_desc', 'partner_stay_pole', 'partner_remarks', 'partner_bill_id', 'self_bill_id', 'type', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sail_schedule_id' => 'integer', 'car_id' => 'integer', 'receipt_status' => 'integer', 'poundbill_status' => 'integer', 'box_reporting_status' => 'integer', 'fh_status' => 'integer', 'rush_status' => 'integer', 'tos' => 'integer', 'ship_company_bill_id' => 'integer', 'motorcade_bill_id' => 'integer', 'partner_id' => 'integer', 'partner_stay_pole' => 'integer', 'partner_bill_id' => 'integer', 'self_bill_id' => 'integer', 'type' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = ['type_text', 'fh_status_text', 'receipt_status_text', 'poundbill_status_text', 'box_reporting_status_text', 'rush_status_text', 'tos_text'];

    public static function getAttributeComment()
    {
        return [
            'id' => '编号',
            'sail_schedule_id' => '船期ID',
            'number' => '海运单',
            'case_number' => '箱号',
            'qf_number' => '铅封号',
            'box' => '箱型',
            'box_main_code' => '箱主代码',
            'good_name' => '商品名',
            'weight' => '重量',
            'ship_company_towing_fee' => '船公司拖车费',
            'ship_company_towing_fee_desc' => '船公司拖车费说明',
            'car_id' => '车辆ID',
            'car_fee' => '司机费用',
            'car_other_fee' => '司机其他费用',
            'car_other_fee_desc' => '其他费用说明',
            'car_finished_date' => '车辆派送日期',
            'receipt_status' => '箱主代码',
            'poundbill_status' => '箱主代码',
            'box_reporting_status' => '箱主代码',
            'liaison' => '联系人',
            'liaison_mobile' => '联系人电话',
            'liaison_address' => '联系人地址',
            'liaison_address_detail' => '联系人地址详情',
            'liaison_remark' => '联系人备注',
            'estimated_time' => '预计卸/装时间',
            'driver_name' => '司机姓名',
            'driver_mobile_number' => '司机电话',
            'driver_id_card' => '司机身份证',
            'fh_status' => '放货状态',
            'rush_status' => '是否加急',
            'tos' => '业务类型',
            'ship_company_bill_id' => 'xxxxx',
            'motorcade_bill_id' => 'xxxxx',
            'partner_id' => 'xxxxx',
            'partner_towing_fee' => '拖车费',
            'partner_overdue_fee' => '滞箱费/超期费',
            'partner_stockpiling_fee' => '码头：堆存费',
            'partner_huandan_fee' => '换单费',
            'partner_thc_fee' => '码头装卸作业费',
            'partner_print_fee' => '打单费',
            'partner_clean_fee' => '洗柜费',
            'partner_other_fee' => '其他费用',
            'partner_other_fee_desc' => '其他费用说明',
            'partner_stay_pole' => '加固杆',
            'partner_remarks' => '备注',
            'partner_bill_id' => 'xxxxx',
            'self_bill_id' => 'xxxxx',
            'type' => '类型',
            'status' => '状态',
            'created_at' => 'xxxxx',
            'updated_at' => 'xxxxx',
            'deleted_at' => 'xxxxx',
        ];
    }

    public static function getFhStatusArray()
    {
        return [self::FH_STATUS_DEFAULT => '-', self::FH_STATUS_NO => '未放货', self::FH_STATUS_YES => '已放货'];
    }

    public static function getReceiptStatusArray()
    {
        return [self::RECEIPT_STATUS_DEFAULT => '-', self::RECEIPT_STATUS_NOT_UPLOAD => '未上传', self::RECEIPT_STATUS_UPLOADED => '已上传'];
    }

    public static function getBoxReportingStatusArray()
    {
        return [self::BOX_REPORTING_STATUS_DEFAULT => '-', self::BOX_REPORTING_STATUS_NOT_EXEC => '未报备', self::BOX_REPORTING_STATUS_EXEC => '已报备'];
    }

    public static function getPoundbillStatusArray()
    {
        return [self::POUNDBILL_STATUS_DEFAULT => '-', self::POUNDBILL_STATUS_NOT_TAKEN => '单未拿回', self::POUNDBILL_STATUS_NOT_POSTED => '未邮寄', self::POUNDBILL_STATUS_POSTED => '已寄'];
    }

    public static function getTypeArray()
    {
        return [self::TYPE_DEFAULT => '-', self::TYPE_CHUKOU => '出口', self::TYPE_JINKOU => '进口'];
    }

    public static function getRushStatusArray()
    {
        return [null => null, self::RUSH_STATUS_NO => '-', self::RUSH_STATUS_YES => '加急'];
    }

    public static function getTosArray()
    {
        return [self::TOS_DEFAULT => '-', self::TOS_DAOMEN => '到门', self::TOS_DAOGANG => '到港', self::TOS_JIAMENDIAN => '假门点'];
    }

    public function sailSchedule()
    {
        return $this->hasOne(WuliuSailSchedule::class, 'id', 'sail_schedule_id');
    }

    public function car()
    {
        return $this->hasOne(WuliuCar::class, 'id', 'car_id');
    }

    public function partner()
    {
        return $this->hasOne(WuliuPartner::class, 'id', 'partner_id');
    }

    public function shipCompanyBill()
    {
        return $this->hasOne(WuliuBill::class, 'id', 'ship_company_bill_id');
    }

    public function motorcadeBill()
    {
        return $this->hasOne(WuliuBill::class, 'id', 'motorcade_bill_id');
    }

    public function partnerBill()
    {
        return $this->hasOne(WuliuBill::class, 'id', 'partner_bill_id');
    }

    public function selfBill()
    {
        return $this->hasOne(WuliuBill::class, 'id', 'self_bill_id');
    }

    /**
     * 根据 运单号+箱号 查出存在的数据.
     * @param mixed $modelsArray
     * @param mixed $number
     * @param mixed $case_number
     * @return bool
     */
    public static function checkIsExistsByNumberAndCaseNumber($modelsArray, $number, $case_number)
    {
        foreach ($modelsArray as $key => $value) {
            if ($value['number'] === $number && $value['case_number'] === $case_number) {
                return true;
            }
        }
        return false;
    }

    /**
     * 根据 运单号+箱号 查出存在的数据.
     * @param mixed $modelsArray
     * @param mixed $number
     * @param mixed $case_number
     */
    public static function getByNumberAndCaseNumber($modelsArray, $number, $case_number)
    {
        foreach ($modelsArray as $value) {
            if ($value['number'] === $number && $value['case_number'] === $case_number) {
                return $value;
            }
        }
        return false;
    }
}

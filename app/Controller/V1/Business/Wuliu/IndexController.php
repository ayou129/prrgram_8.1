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

namespace App\Controller\V1\Business\Wuliu;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Model\WuliuSeaWaybill;
use Hyperf\DbConnection\Db;

class IndexController extends AbstractController
{
    public function data()
    {
        // 海运单单量
        // 本年
        $yearStartDate = date('Y-m-d H:i:s', strtotime(date('Y') . '-1-1'));
        $yearEndDate = date('Y-m-d H:i:s', strtotime(date('Y') . '-12-31 23:59:59'));
        $seaWaybillYearCount = WuliuSeaWaybill::where('created_at', '>=', $yearStartDate)
            ->where('created_at', '<=', $yearEndDate)
            ->count();

        // 上季度
        $season = ceil(date('n') / 3) - 1;
        $lastQuarterStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) $season * 3 - 3 + 1, 1, (int) date('Y')));
        $lastQuarterEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) ($season * 3), (int) date('t', mktime(0, 0, 0, (int) ($season * 3), 1, (int) date('Y'))), (int) date('Y')));
        $seaWaybillLastQuarterCount = WuliuSeaWaybill::where('created_at', '>=', $lastQuarterStartDate)
            ->where('created_at', '<=', $lastQuarterEndDate)
            ->count();

        // 本季度
        $season = ceil(date('n') / 3);
        $quarterStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) ($season * 3 - 3 + 1), 1, (int) date('Y')));
        $quarterEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) ($season * 3), (int) date('t', mktime(0, 0, 0, (int) ($season * 3), 1, (int) date('Y'))), (int) date('Y')));
        $seaWaybillQuarterCount = WuliuSeaWaybill::where('created_at', '>=', $quarterStartDate)
            ->where('created_at', '<=', $quarterEndDate)
            ->count();

        // 上月
        $lastMonthStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n') - 1, 1, (int) date('Y')));
        $lastMonthEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), 0, (int) date('Y')));
        $seaWaybillLastMonthCount = WuliuSeaWaybill::where('created_at', '>=', $lastMonthStartDate)
            ->where('created_at', '<=', $lastMonthEndDate)
            ->count();

        // 本月
        $monthStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y')));
        $monthEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), (int) date('t'), (int) date('Y')));
        $seaWaybillMonthCount = WuliuSeaWaybill::where('created_at', '>=', $monthStartDate)
            ->where('created_at', '<=', $monthEndDate)
            ->count();

        // 上周 周一-周日
        $lastWeekStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), date('d') - date('w') + 1 - 7, (int) date('Y')));
        $lastWeekEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), date('d') - date('w') + 7 - 7, (int) date('Y')));
        $seaWaybillLastWeekCount = WuliuSeaWaybill::where('created_at', '>=', $lastWeekStartDate)
            ->where('created_at', '<=', $lastWeekEndDate)
            ->count();

        // 本周
        $weekStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), date('d') - date('w') + 1, (int) date('Y')));
        $weekEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), date('d') - date('w') + 7, (int) date('Y')));
        $seaWaybillWeekCount = WuliuSeaWaybill::where('created_at', '>=', $weekStartDate)
            ->where('created_at', '<=', $weekEndDate)
            ->count();

        # 海运单流程
        // 待派车数量
        $seaWaybillNoPaicheCount = WuliuSeaWaybill::where('car_id', null)
            ->whereOr('car_finished_date', null)
            ->count();
        // 未处理签收数量
        $seaWaybillNoExecReceiptStatusCount = WuliuSeaWaybill::where('receipt_status', WuliuSeaWaybill::RECEIPT_STATUS_NOT_UPLOAD)
            ->count();
        // 未处理磅单数量
        $seaWaybillNoExecPoundbillStatusCount = WuliuSeaWaybill::where('poundbill_status', WuliuSeaWaybill::POUNDBILL_STATUS_NOT_TAKEN)
            ->count();

        # 海运单账单

        # 司机
        // 接单数量排行 季度 月
        // $carRankingArray = [];
        $carQuarterquarterStartDate = WuliuSeaWaybill::where('created_at', '>=', $quarterStartDate)
            ->where('created_at', '<=', $quarterEndDate)
            ->select('car_id', Db::raw('count(car_id) as count'))
            ->groupBy('car_id')
            ->with(['car'])
            ->orderBy(Db::raw('count(car_id)'), 'desc')
            ->get();
        $carMonthRankingArray = WuliuSeaWaybill::where('created_at', '>=', $monthStartDate)
            ->where('created_at', '<=', $monthEndDate)
            ->select('car_id', Db::raw('count(car_id) as count'))
            ->groupBy('car_id')
            ->with(['car'])
            ->orderBy(Db::raw('count(car_id)'), 'desc')
            ->get();

        $links = [];
        $links[] = [
            'title' => '中谷订舱',
            'url' => 'http://dingcang.zhonggu56.com/index#/',
            'account' => [],
        ];
        $links[] = [
            'title' => '中谷驳船管理',
            'url' => 'http://dingcang.zhonggu56.com/index#/',
            'account' => [],
        ];
        $links[] = [
            'title' => '中谷拖车',
            'url' => 'http://tuoche.zhonggu56.com/index#/',
            'account' => [],
        ];
        $links[] = [
            'title' => '中谷报备(工具箱)',
            'url' => 'http://toolweb.zhonggu56.com:12001/xgl.aspx',
            'account' => [],
        ];
        $links[] = [
            'title' => '中谷货物跟踪1',
            'url' => 'http://toolweb.zhonggu56.com:12001/Other/HWGZ.aspx',
            'account' => [],
        ];
        $links[] = [
            'title' => '中谷货物跟踪2',
            'url' => 'http://dingcang.zhonggu56.com/views/CargoStrack/CargoStrack.jsp',
            'account' => [],
        ];
        $links[] = [
            'title' => '安通订舱查询',
            'url' => 'https://www.antong56.com/dc/freight/freightlist',
            'account' => [],
        ];
        $links[] = [
            'title' => '安通订舱',
            'url' => 'https://www.antong56.com/main',
            'account' => ['zjtcwlyxgs', 'a2678700'],
        ];
        $links[] = [
            'title' => '安通集卡管理',
            'url' => 'https://jk.antong56.com/',
            'account' => ['admin', '888888', 'zjtcwl5'],
        ];
        $links[] = [
            'title' => '安通订舱货物跟踪',
            'url' => 'https://www.antong56.com/biz/tracking',
            'account' => [],
        ];
        $links[] = [
            'title' => '安通放货查询',
            'url' => 'https://www.antong56.com/dc/online/putboxsearch',
            'account' => [],
        ];
        $links[] = [
            'title' => '湛江港在线办单',
            'url' => 'https://ictwx.zjport.com/eir/goLogin',
            'account' => ['ZJTCWL-DZ', 'ZJtc123*'],
        ];
        $links[] = [
            'title' => '湛江港数据查询',
            'url' => 'http://219.132.70.181:8000/',
            'account' => [],
        ];
        $links[] = [
            'title' => '湛江港拖车预约',
            'url' => 'https://ictwx.zjport.com/TruckReservation/page/TruckJob/TruckJobPath?path=home',
            'account' => ['ZJ_ckyf', '1234@zjport.COM'],
        ];
        $links[] = [
            'title' => '中远货物跟踪',
            'url' => 'https://elines.coscoshipping.com/ebusiness/',
            'account' => [],
        ];
        $links[] = [
            'title' => '中远进口费用开票与支付',
            'url' => 'https://elines.coscoshipping.com/ebusiness/efdo/blNo',
            'account' => [],
        ];
        $links[] = [
            'title' => '泛亚 货物跟踪',
            'url' => 'https://eportal.epanasia.com/cargoTracking',
            'account' => [],
        ];
        $links[] = [
            'title' => '信风',
            'url' => 'http://dc.trawind.com/welcome',
            'account' => [],
        ];
        $links[] = [
            'title' => '中国石化',
            'url' => 'https://www.sinopecsales.com/',
            'account' => [],
        ];
        $links[] = [
            'title' => '中国石油',
            'url' => 'https://www.95504.net/',
            'account' => [],
        ];
        $result = [
            'seaWaybillYearCount' => $seaWaybillYearCount,
            'seaWaybillLastQuarterCount' => $seaWaybillLastQuarterCount,
            'seaWaybillQuarterCount' => $seaWaybillQuarterCount,
            'seaWaybillLastMonthCount' => $seaWaybillLastMonthCount,
            'seaWaybillMonthCount' => $seaWaybillMonthCount,
            'seaWaybillLastWeekCount' => $seaWaybillLastWeekCount,
            'seaWaybillWeekCount' => $seaWaybillWeekCount,
            'seaWaybillNoExecReceiptStatusCount' => $seaWaybillNoExecReceiptStatusCount,
            'seaWaybillNoExecPoundbillStatusCount' => $seaWaybillNoExecPoundbillStatusCount,
            'seaWaybillNoPaicheCount' => $seaWaybillNoPaicheCount,
            'carQuarterquarterStartDate' => $carQuarterquarterStartDate->toArray(),
            'carMonthRankingArray' => $carMonthRankingArray->toArray(),
            'links' => $links,
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }
}

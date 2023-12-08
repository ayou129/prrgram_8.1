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

namespace App\Command;

use App\Model\WuliuSeaWaybill;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * php bin/hyperf.php Demo --help.
 * @Command
 */
#[Command]
class Demo extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('Demo');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('app test Command');
    }

    public function handle()
    {
        $this->line('start', 'info');
        // // 本年开始
        // $yearStartDate = date('Y-m-d H:i:s', strtotime(date('Y') . '-1-1'));
        // // 本年结束
        // $yearEndDate = date('Y-m-d H:i:s', strtotime(date('Y') . '-12-31 23:59:59'));
        // $seaWaybillYearCount = WuliuSeaWaybill::where('created_at', '>=', $yearStartDate)
        //     ->where('created_at', '<=', $yearEndDate)
        //     ->count();
        // var_dump($seaWaybillYearCount);

        // $monthStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y')));
        // $monthEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), (int) date('t'), (int) date('Y')));
        // $seaWaybillMonthount = WuliuSeaWaybill::where('created_at', '>=', $monthStartDate)
        //     ->where('created_at', '<=', $monthEndDate)
        //     ->count();
        // var_dump($monthStartDate, $monthEndDate, $seaWaybillMonthount);

        // $lastMonthStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n') - 1, 1, (int) date('Y')));
        // $lastMonthEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), 0, (int) date('Y')));
        // var_dump(1, $lastMonthStartDate, $lastMonthEndDate);

        // $lastWeekStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), date('d') - date('w') + 1 - 7, (int) date('Y')));
        // $lastWeekEndDate = date('Y'-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), date('d') - date('w') + 7 - 7, (int) date('Y')));
        // var_dump(1, $lastWeekStartDate, $lastWeekEndDate);

        // $weekStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) date('n'), date('d') - date('w') + 1, (int) date('Y')));
        // $weekEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) date('n'), date('d') - date('w') + 7, (int) date('Y')));
        // var_dump(1, $weekStartDate, $weekEndDate);
        // $season = ceil(date('n') / 3);
        // $quarterStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) $season * 3 - 3 + 1, 1, (int) date('Y')));
        // $quarterEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) ($season * 3), (int) date('t', mktime(0, 0, 0, (int) ($season * 3), 1, (int) date('Y'))), (int) date('Y')));
        // var_dump($quarterStartDate, $quarterEndDate);

        // $season = ceil(date('n') / 3) - 1;
        // $lastQuarterStartDate = date('Y-m-d H:i:s', mktime(0, 0, 0, (int) $season * 3 - 3 + 1, 1, (int) date('Y')));
        // $lastQuarterEndDate = date('Y-m-d H:i:s', mktime(23, 59, 59, (int) ($season * 3), (int) date('t', mktime(0, 0, 0, (int) ($season * 3), 1, (int) date('Y'))), (int) date('Y')));
        // var_dump($lastQuarterStartDate, $lastQuarterEndDate);
    }
}

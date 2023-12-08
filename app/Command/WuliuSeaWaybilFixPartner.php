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
 * php bin/hyperf.php WuliuSeaWaybilFixPartner --help.
 * @Command
 */
class WuliuSeaWaybilFixPartner extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('WuliuSeaWaybilFixPartner');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('海运单处理数据');
    }

    public function handle()
    {
        // 玖鼎补差账单2023年
        $data = [
            ['number' => 'ZGC22123552810', 'case_number' => 'ZGXU6158712', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22123552810', 'case_number' => 'TGBU6010070', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22123552810', 'case_number' => 'ZGXU6123700', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22123552810', 'case_number' => 'TLLU5333464', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22123552810', 'case_number' => 'ZGXU6330354', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 35],
            ['number' => 'ZGC22123552810', 'case_number' => 'ZGXU6277455', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018605378', 'case_number' => 'BEAU4645690', 'partner_towing_fee' => 800, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018605378', 'case_number' => 'ZGXU6325698', 'partner_towing_fee' => 800, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013593648', 'case_number' => 'TCNU2801555', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018639896', 'case_number' => 'ZGXU6269341', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013593648', 'case_number' => 'SEGU6446339', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013614812', 'case_number' => 'ZGLU8014168', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013593648', 'case_number' => 'ZGXU6156915', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013593648', 'case_number' => 'ZGXU6198470', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23013614812', 'case_number' => 'ZGXU6524112', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018639896', 'case_number' => 'ZGXU6218616', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22128600380', 'case_number' => 'TEMU6932949', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC22128600380', 'case_number' => 'ZGXU6242525', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018692104', 'case_number' => 'TGBU5431562', 'partner_towing_fee' => 800, 'partner_stockpiling_fee' => 0],
            ['number' => 'ZGC23018692104', 'case_number' => 'FCIU7332049', 'partner_towing_fee' => 650, 'partner_stockpiling_fee' => 0],
        ];

        foreach ($data as $key => $value) {
            $model = WuliuSeaWaybill::where('number', $value['number'])
                ->where('case_number', $value['case_number'])
                ->first();
            if (! $model) {
                $this->line('数据不存在', $value);
                continue;
            }
            $model->partner_towing_fee = $value['partner_towing_fee'];
            $model->partner_stockpiling_fee = $value['partner_stockpiling_fee'];
            $model->save();
        }

        $this->line('done!', 'info');
    }
}

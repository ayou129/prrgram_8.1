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

use App\Model\WuliuPartner;
use App\Model\WuliuSeaWaybill;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * php bin/hyperf.php WuliuPartnerRepeatFix --help.
 * @Command
 */
#[Command]
class WuliuPartnerRepeatFix extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('WuliuPartnerRepeatFix');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('修复重复数据');
    }

    public function handle()
    {
        // 查询出有问题的车辆
        // 指定两个id
        // $error_id,$fix_id
        // 171 -> 15
        // 518 -> 557
        $error_id = $this->input->getArgument('error_id');
        $fix_id = $this->input->getArgument('fix_id');
        $errorModel = WuliuPartner::find($error_id);
        $fixModel = WuliuPartner::find($fix_id);
        if (! $errorModel) {
            $this->line('error数据不存在', 'error');
            return;
        }
        if (! $fixModel) {
            $this->line('fix数据不存在', 'error');
            return;
        }
        $this->line('start', 'info');
        (new WuliuSeaWaybill())->where('partner_id', $error_id)->update(['partner_id' => $fix_id]);
        $errorModel->delete();
        $this->line('done!', 'info');
    }

    protected function getArguments()
    {
        return [
            ['error_id', InputArgument::REQUIRED, '出错的ID'],
            ['fix_id', InputArgument::REQUIRED, '修复至ID'],
        ];
    }
}

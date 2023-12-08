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
use App\Model\WuliuShipCompany;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;

/**
 * php bin/hyperf.php WuliuSeaWaybillNumberAutoShipCompany --help.
 * @Command
 */
#[Command]
class WuliuSeaWaybillNumberAutoShipCompany extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('WuliuSeaWaybillNumberAutoShipCompany');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('海运单自动更新船公司');
    }

    public function handle()
    {
        $this->line('start', 'info');
        $seaWaybillModels = WuliuSeaWaybill::where('ship_company_id', null)->select('id', 'number', 'ship_company_id')->get();
        // var_dump($seaWaybillModels->count());
        $updateArray = [];
        foreach ($seaWaybillModels as $key => $seaWaybillModel) {
            // if($seaWaybillModel->id == '20433'){
            // var_dump(substr($seaWaybillModel->number, 0, 1));
            // }
            $ship_company_id = WuliuShipCompany::getIdBySeaWaybillNumber($seaWaybillModel->number);
            if ($ship_company_id) {
                $updateArray[] = [
                    'id' => $seaWaybillModel->id,
                    'ship_company_id' => $ship_company_id,
                ];
            }
        }
        // var_dump($updateArray, count($updateArray));
        var_dump(count($updateArray));
        if ($updateArray) {
            (new WuliuSeaWaybill())->updateBatch($updateArray);
        }
        $this->line('done!', 'info');
    }
}

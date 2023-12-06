<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */
namespace App\Command;

use App\Model\WuliuSailSchedule;
use App\Model\WuliuSeaWaybill;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * php bin/hyperf.php WuliuSailScheduleRepeatFix --help.
 * @Command
 */
#[Command]
class WuliuSailScheduleRepeatFix extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct('WuliuSailScheduleRepeatFix');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('修复重复船期数据');
    }

    public function handle()
    {
        // 查出所有有问题的数据
        // $sailScheduleMatchingModels = WuliuSailSchedule::get();
        // $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
        // $data = [];
        // $error_ids_set = [];
        // foreach ($sailScheduleMatchingModelsArray as $key => $value) {
        //     if (in_array($value['id'], $error_ids_set)) {
        //         continue;
        //     }
        //     $name = $value['name'];
        //     $voyage = $value['voyage'];
        //     $fix_id = $value['id'];
        //     $arrival_date = $value['arrival_date'] ?: null;
        //     $errorIds = [];
        //     foreach ($sailScheduleMatchingModelsArray as $key2 => $value2) {
        //         if ($name == $value2['name'] && $voyage == $value2['voyage'] && $fix_id != $value2['id']) {
        //             // find
        //             if (! $arrival_date && $value2['arrival_date']) {
        //                 $arrival_date = $value2['arrival_date'];
        //             }
        //             $errorIds[] = $value2['id'];
        //             // break;
        //         }
        //     }
        //     if ($errorIds) {
        //         $data[] = [
        //             'fix_id' => $fix_id,
        //             'error_ids' => $errorIds,
        //             'arrival_date' => $arrival_date,
        //         ];
        //         $error_ids_set = array_merge($errorIds,$error_ids_set);
        //         // $error_ids_set[] =$fix_id;
        //     }
        // }
        // foreach ($data as $key => $value) {
        //     $error_ids = $value['error_ids'];
        //     $fix_id = $value['fix_id'];
        //     $fixModel = WuliuSailSchedule::find($fix_id);
        //     if (! $fixModel) {
        //         $this->line('fix数据不存在' . $fix_id, 'error');
        //         continue;
        //     }
        //     (new WuliuSeaWaybill())->whereIn('sail_schedule_id', $error_ids)->update(['sail_schedule_id' => $fix_id]);
        //     if ($value['arrival_date']) {
        //         WuliuSailSchedule::where('id', $fix_id)->update(['arrival_date' => $value['arrival_date']]);
        //     }
        //     WuliuSailSchedule::whereIn('id', $error_ids)->delete();
        // }

        // var_dump($data);
        // 查询出有问题的车辆
        // 指定两个id
        // $error_id,$fix_id
        // 33->339(原始：)
        $error_id = $this->input->getArgument('error_id');
        $fix_id = $this->input->getArgument('fix_id');
        $errorModel = WuliuSailSchedule::find($error_id);
        $fixModel = WuliuSailSchedule::find($fix_id);
        if (! $errorModel) {
            $this->line('error数据不存在', 'error');
            return;
        }
        if (! $fixModel) {
            $this->line('fix数据不存在', 'error');
            return;
        }
        $this->line('start', 'info');
        (new WuliuSeaWaybill())->where('sail_schedule_id', $error_id)->update(['sail_schedule_id' => $fix_id]);
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

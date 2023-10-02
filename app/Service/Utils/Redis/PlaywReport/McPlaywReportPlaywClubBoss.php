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

namespace App\Service\Utils\Redis\PlaywReport;

use App\Model\PlaywReportPlaywClubBoss;
use App\Service\Utils\Redis\PlaywReport\MCStrategy\MCStrategyAbstract;
use Hyperf\Redis\Redis;

class McPlaywReportPlaywClubBoss extends MCStrategyAbstract
{
    public const ttl = 3600 * 24 * 2;

    public string $table = '';

    public function __construct(?Redis $redis)
    {
        $this->table = (new PlaywReportPlaywClubBoss())->getTable();
        parent::__construct($redis);
    }
}

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

namespace App\Listener;

use App\Model\SysRequest;
use App\Model\SysRequestSql;
use App\Utils\Tools;
use Hyperf\Collection\Arr;
use Hyperf\Context\Context;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[Listener]
class DbQueryExecutedListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql', 'sql');
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        if ($event instanceof QueryExecuted) {
            $sql = $event->sql;
            if (! Arr::isAssoc($event->bindings)) {
                $position = 0;
                foreach ($event->bindings as $value) {
                    $position = strpos($sql, '?', $position);
                    if ($position === false) {
                        break;
                    }
                    $value = "'{$value}'";
                    $sql = substr_replace($sql, $value, $position, 1);
                    $position += strlen($value);
                }
            }

            $this->logger->info(sprintf('[%s] %s', $event->time, $sql));

            $requestModel = Context::get('requestModel');
            $requestTable = (new SysRequest())->getTable();
            $requestSqlTable = (new SysRequestSql())->getTable();
            if ($requestModel) {
                if (! (strpos($sql, $requestTable) !== false) && ! (strpos($sql, $requestSqlTable) !== false)) {
                    $requestSqlModel = new SysRequestSql();
                    $requestSqlModel->request_id = $requestModel->id;
                    $requestSqlModel->sql = $sql;
                    $requestSqlModel->sql_exec_time = $event->time;
                    $requestSqlModel->created_at = Tools::getNowDate();
                    $requestSqlModel->save();
                }
            }
        }
    }
}

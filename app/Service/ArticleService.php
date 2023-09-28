<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service;

use App\Model\Article;

class ArticleService
{
    public function list($params)
    {
        $articleModels = new Article();
        if (isset($params['created_at']['start_time'])) {
            $articleModels->where('created_at', '>=', $params['created_at']['start_time']);
        }
        if (isset($params['created_at']['end_time'])) {
            $articleModels->where('created_at', '<=', $params['created_at']['end_time']);
        }
        return $articleModels->paginate(10);
    }
}

<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property int $pid
 * @property string $title
 * @property string $icon
 * @property string $path
 * @property string $component
 * @property string $name
 * @property string $redirect
 * @property int $always_show
 * @property int $index
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Hyperf\Database\Model\Collection|self[] $children
 */
class AdminMenu extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_menu';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'pid', 'title', 'icon', 'path', 'component', 'name', 'redirect', 'always_show', 'index', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'always_show' => 'integer', 'index' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function children()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }
}

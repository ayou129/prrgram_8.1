<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use HyperfExt\Jwt\Contracts\JwtSubjectInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $nickname
 * @property string $avatar
 * @property string $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property AdminGroupRelation[]|\Hyperf\Database\Model\Collection $groups
 */
class Admin extends BaseModel implements JwtSubjectInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'username', 'password', 'nickname', 'avatar', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $hidden = ['password'];

    public function getJwtIdentifier()
    {
        return $this->getKey();
    }

    public function getJwtCustomClaims(): array
    {
        return [];
    }

    public function groups()
    {
        return $this->hasMany(AdminGroupRelation::class, 'admin_id', 'id');
    }
}

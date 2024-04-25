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

namespace App\Model;

// use App\Service\Utils\Redis\PlaywReport\McUser;
// use App\Service\Utils\Redis\PlaywReport\ModelCacheTrait;
/**
 * @property int $id
 * @property string $phone
 * @property string $wx_unionid
 * @property string $password
 * @property string $real_name
 * @property string $nickname
 * @property string $avatar_url
 * @property int $gender
 * @property string $constellation
 * @property string $city
 * @property string $province
 * @property string $country
 * @property int $status
 * @property string $playw_report_playwname
 * @property int $playw_report_club_jiedan_price
 * @property int $playw_report_club_id
 * @property string $playw_report_club_join_at
 * @property int $playw_report_club_admin
 * @property string $social_dazzle_nickname
 * @property string $social_signature
 * @property int $social_charm_value
 * @property int $social_magnate_value
 * @property int $social_noble_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $birthday
 * @property mixed $social_id
 * @property null|UserPlatform $platform
 * @property null|\Hyperf\Database\Model\Collection|UserPlatform[] $platforms
 */
class User extends BaseModel
{
    // use ModelCacheTrait;

    public const PLAYW_REPORT_CLUB_ADMIN_DEFAULT = 0;

    public const PLAYW_REPORT_CLUB_ADMIN_YES = 1;

    public ?UserPlatform $platformMiniprogram;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'phone', 'wx_unionid', 'password', 'real_name', 'nickname', 'avatar_url', 'gender', 'birthday', 'constellation', 'city', 'province', 'country', 'status', 'playw_report_playwname', 'playw_report_club_jiedan_price', 'playw_report_club_id', 'playw_report_club_join_at', 'playw_report_club_admin', 'social_id', 'social_dazzle_nickname', 'social_signature', 'social_charm_value', 'social_magnate_value', 'social_noble_value', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer', 'status' => 'integer', 'playw_report_club_jiedan_price' => 'integer', 'playw_report_club_id' => 'integer', 'playw_report_club_admin' => 'integer', 'social_id' => 'integer', 'social_charm_value' => 'integer', 'social_magnate_value' => 'integer', 'social_noble_value' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $hidden = ['password'];

    public static function getPlaywReportClubAdminArray()
    {
        return [
            self::PLAYW_REPORT_CLUB_ADMIN_DEFAULT => '否',
            self::PLAYW_REPORT_CLUB_ADMIN_YES => '是',
        ];
    }

    public function setBirthdayAttribute($v)
    {
        $this->attributes['birthday'] = $v === '' ? null : $v;
    }

    public function setSocialIdAttribute($v)
    {
        $this->attributes['social_id'] = $v === '' ? null : $v;
    }

    public function platform()
    {
        return $this->hasOne(UserPlatform::class, 'u_id', 'id');
    }

    public function platforms()
    {
        return $this->hasMany(UserPlatform::class, 'u_id', 'id');
    }

    // /**
    //  * @param mixed $k
    //  * @param mixed $relations
    //  * @return null|\Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object|User
    //  */
    // public static function getCacheUserByPhone($k, $relations = [])
    // {
    //     $redis = make(\Hyperf\Redis\Redis::class);
    //     $mc = new McUser($redis);
    //     $id = $mc->getByPhone($k);
    //     if ($id) {
    //         $cache = $mc->getModel($id);
    //         if ($cache) {
    //             $model = (new self())->newInstance($cache, true);
    //         } else {
    //             $model = null;
    //         }
    //     } else {
    //         $model = (new self())->where('phone', $k)
    //             ->first();
    //     }
    //     self::addRelations($model, $relations);
    //     return $model ?? null;
    // }

    public static function addAttrText(&$model)
    {
        if ($model) {
            $model->label = $model->playw_report_playwname ?: '未设置昵称';
            $model->onshow = true;
            $model->playw_report_club_admin_text = self::getPlaywReportClubAdminArray()[$model->playw_report_club_admin];
        }
    }
}

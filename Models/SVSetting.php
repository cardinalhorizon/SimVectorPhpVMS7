<?php

namespace Modules\SimVector\Models;

use App\Contracts\Model;

/**
 * Class SVSetting
 * @package Modules\SimVector\Models
 */
class SVSetting extends Model
{
    public $table = 'sv_settings';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getModuleSettings($module)
    {

        $keys = (new SVSetting)->get();
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key->key] = $key->value;
        }
        return $settings;
    }
}

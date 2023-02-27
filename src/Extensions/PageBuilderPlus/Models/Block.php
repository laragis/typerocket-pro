<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus\Models;

use \TypeRocket\Models\Model;
use TypeRocket\Models\WPUser;
use TypeRocket\Utility\Url;
use TypeRocket\Pro\Extensions\PageBuilderPlus\Traits\BuilderRevisions;

/**
 * @property int $id
 * @property int $user_id
 * @property int $last_user_id
 * @property string $title
 * @property array $blocks
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property WPUser|Model $lastUser
 * @property WPUser|Model $user
 *
 */
class Block extends Model
{
    use BuilderRevisions;

    protected $resource = 'tr_blocks';
    protected $cast = [
        'blocks' => 'array',
        'id' => 'int',
        'user_id' => 'int',
        'last_user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public CONST FIELD_NAME = 'blocks';

    public function getSearchColumn()
    {
        return 'title';
    }

    public function user()
    {
        $userClass = \TypeRocket\Utility\Helper::modelClass('User', false);
        return $this->belongsTo($userClass, 'user_id');
    }

    public function lastUser()
    {
        $userClass = \TypeRocket\Utility\Helper::modelClass('User', false);
        return $this->belongsTo($userClass, 'last_user_id');
    }

    /**
     * @return string|void|null
     */
    public function getSearchUrl()
    {
        return admin_url('admin.php?page=block_edit&route_args%5B0%5D=' . $this->getID());
    }
}
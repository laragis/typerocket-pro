<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus\Models;

use TypeRocket\Models\Model;
use App\Models;
use App\Models\User;
use App\Models\Page;
use TypeRocket\Utility\Url;

/**
 * Class BuilderRevision
 * @package App\Models
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $model
 * @property string $field_name
 * @property string $components
 * @property string $created_at
 * @property Page $page
 * @property Block $block
 */
class BuilderRevision extends Model
{
    protected $resource = 'tr_builder_revisions';

    protected $format = [
        'components' => 'serialize'
    ];

    protected $cast = [
        'components' => 'array',
        'id' => 'int',
        'post_id' => 'int',
        'user_id' => 'int',
    ];

    public function modelConnection($class)
    {
        return $this->where('model', $class)->belongsTo($class, 'post_id');
    }

    public function page()
    {
        $pageClass = \TypeRocket\Utility\Helper::modelClass('Page', false);
        return $this->where('model', $pageClass)->belongsTo($pageClass, 'post_id');
    }

    public function user()
    {
        $userClass = \TypeRocket\Utility\Helper::modelClass('User', false);
        return $this->where('model', $userClass)->belongsTo($userClass, 'user_id');
    }

    public function block()
    {
        return $this->where('model', Block::class)->belongsTo(Block::class, 'post_id');
    }

    public function getComponents()
    {
        $pageClass = \TypeRocket\Utility\Helper::modelClass('Page', false);
        if(trim($this->model, '\\') === trim($pageClass, '\\') ) {
            $pageModel = $this->modelConnection($pageClass)->with('meta')->get();
            return $pageModel->meta ? $pageModel->meta->builder : null;
        }

        if($this->model === Block::class) {
            return $this->block->blocks;
        }

        return null;
    }

    public function getParentUrl()
    {
        $pageClass = \TypeRocket\Utility\Helper::modelClass('Page', false);
        if(trim($this->model, '\\') === trim($pageClass, '\\')) {
            return get_edit_post_link($this->post_id);
        }

        if($this->model === Block::class) {
            return $this->block->getSearchUrl();
        }

        return null;
    }

    public static function maybeSaveRevision(Model $model, $old, $new)
    {
        if($old != $new) {
            static::newRevision($model->getID(), $old, get_class($model));
        }
    }

    public static function newPageRevision($post_id, $components)
    {
        return static::newRevision($post_id, $components, Page::class);
    }

    public static function getNumRevisionsAllowed()
    {
        $keep = WP_POST_REVISIONS;

        if(WP_POST_REVISIONS === true || empty(WP_POST_REVISIONS)) {
            $keep = 10;
        }

        return (int) $keep;
    }

    public static function newRevision($post_id, $components, $model = null)
    {
        $pageClass = \TypeRocket\Utility\Helper::modelClass('Page', false);
        $class = $model instanceof Model ? get_class($model) : ($model ?? $pageClass);

        switch ($class) {
            case Block::class;
                $field = 'blocks';
                break;
            default;
                $field = 'builder';
                break;
        }

        $new = new static();
        $new->post_id = $post_id;
        $new->user_id = get_current_user_id();
        $new->model = $class;
        $new->components = $components;
        $new->field_name = $field;
        $new->created_at = $new->getDateTime();
        $saved = $new->save();
        $keep = static::getNumRevisionsAllowed();

        // clean up old revisions
        /** @var BuilderRevision[] $items */
        if($items = (new static)->where('post_id', $post_id)->where('model', $class)->orderBy('created_at', 'DESC')->take(20, $keep, false)->get()) {
            foreach ($items as $item) {
                $item->where($item->getIdColumn(), $item->getID())->delete();
            }
        }

        return $saved;
    }

    public function getSearchUrl()
    {
        return Url::build()->adminPage('builder_revisions_edit', [
            'route_args' => [$this->getID()]
        ]);
    }
}
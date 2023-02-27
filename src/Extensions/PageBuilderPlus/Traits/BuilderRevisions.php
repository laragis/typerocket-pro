<?php 
namespace TypeRocket\Pro\Extensions\PageBuilderPlus\Traits;

use TypeRocket\Pro\Extensions\PageBuilderPlus\Models\BuilderRevision;

trait BuilderRevisions
{
    public function builder_revisions()
    {
        $className = static::class;

        return $this->hasMany(BuilderRevision::class, 'post_id', function($rel) use ($className) {
            $rel->where('model', $className);
        });
    }
}
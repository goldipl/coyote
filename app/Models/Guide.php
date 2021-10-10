<?php

namespace Coyote\Models;

use Coyote\Tag;
use Coyote\Taggable;
use Coyote\User;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property User $user
 * @property string $title
 * @property string $excerpt
 * @property string $text
 * @property Tag[] $tags
 * @property int $user_id
 * @property Comment[] $comments
 * @property Comment[] $commentsWithChildren
 */
class Guide extends Model
{
    use Taggable;

    protected $fillable = ['title', 'excerpt', 'text'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'resource', 'tag_resources');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'content');
    }

    public function commentsWithChildren()
    {
        $userRelation = fn ($builder) => $builder->select(['id', 'name', 'photo', 'deleted_at', 'is_blocked'])->withTrashed();

        return $this
            ->comments()
            ->whereNull('parent_id')
            ->orderBy('id', 'DESC')
            ->with([
                'children' => function ($builder) use ($userRelation) {
                    return $builder->with(['user' => $userRelation]);
                },
                'user' => $userRelation
            ]);
    }
}

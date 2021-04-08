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
}
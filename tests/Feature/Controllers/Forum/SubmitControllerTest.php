<?php

namespace Tests\Feature\Controllers\Forum;

use Coyote\Forum;
use Coyote\Permission;
use Coyote\Post;
use Coyote\Topic;
use Coyote\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SubmitControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var Forum
     */
    private $forum;

    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->forum = factory(Forum::class)->create();
        $this->user = $this->createUserWithGroup();
    }

    public function testSubmitWithInvalidTags()
    {
        $this->forum->require_tag = true;
        $this->forum->save();

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit");
        $response->assertJsonValidationErrors(['subject', 'text', 'tags']);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit",
            ['tags' => ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']]
        );

        $response->assertJsonValidationErrors(['tags']);

        $this->forum->require_tag = false;
        $this->forum->save();

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit");
        $response->assertJsonMissingValidationErrors(['tags']);
    }

    public function testSubmitTopicWithPost()
    {
        $post = factory(Post::class)->make();
        $faker = Factory::create();

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit",
            ['text' => $post->text, 'subject' => $faker->text(50), 'is_sticky' => true, 'is_subscribed' => true]
        );

        $response->assertJsonFragment([
            'text' => $post->text
        ]);

        $id = $response->decodeResponseJson('id');

        $this->assertDatabaseHas('posts', ['id' => $id]);
        $this->assertDatabaseHas('topics', ['first_post_id' => $id, 'is_sticky' => false]);

        /** @var Topic $topic */
        $topic = Topic::where('first_post_id', $id)->first();

        $this->assertTrue($topic->subscribers()->forUser($this->user->id)->exists());
    }

    public function testSubmitStickyTopic()
    {
        $faker = Factory::create();

        $permission = Permission::where('name', 'forum-sticky')->get()->first();
        $group = $this->user->groups()->first();

        $this->forum->permissions()->create(['value' => 1, 'group_id' => $group->id, 'permission_id' => $permission->id]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit",
            ['text' => $faker->text, 'subject' => $faker->text(50), 'is_sticky' => true]
        );

        $id = $response->decodeResponseJson('id');

        $this->assertDatabaseHas('posts', ['id' => $id]);
        $this->assertDatabaseHas('topics', ['first_post_id' => $id, 'is_sticky' => true]);
    }

    public function testSubmitPostToExistingTopic()
    {
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);
        $post = factory(Post::class)->make();

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit/{$topic->id}", ['text' => $post->text]);

        $response->assertJsonFragment([
            'text' => $post->text,
            'is_read' => false,
            'is_locked' => false
        ]);

        $this->assertFalse($topic->subscribers()->forUser($this->user->id)->exists());

        $this->assertDatabaseHas('forum_track', ['forum_id' => $this->forum->id, 'guest_id' => $this->user->guest_id]);
    }

    public function testSubmitPostToExistingTopicWhereTagIsRequired()
    {
        $this->forum->require_tag = true;
        $this->forum->save();

        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);
        $post = factory(Post::class)->make();

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit/{$topic->id}", ['text' => $post->text]);

        $response->assertStatus(200);
    }

    public function testEditExistingPostByAuthor()
    {
        $faker = Factory::create();
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);

        factory(Post::class)->create(['forum_id' => $this->forum->id, 'topic_id' => $topic->id]);
        $post = factory(Post::class)->create(['user_id' => $this->user->id, 'forum_id' => $this->forum->id, 'topic_id' => $topic->id]);

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$post->id}", ['text' => $text = $faker->text]);

        $response->assertJsonFragment([
            'text' => $text
        ]);
    }

    public function testEditExistingPostByAnotherUser()
    {
        $faker = Factory::create();
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);

        $post = factory(Post::class)->create(['forum_id' => $this->forum->id, 'topic_id' => $topic->id]);

        $response = $this->json('POST', "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$post->id}", ['text' => $faker->text]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->user)->json('POST', "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$post->id}", ['text' => $faker->text]);
        $response->assertStatus(403);
    }

    public function testChangeTopicSubject()
    {
        $faker = Factory::create();
        /** @var Topic $topic */
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);

        $post = $topic->posts()->first();

        $post->user_id = $this->user->id;
        $post->save();

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$post->id}",
            ['text' => $text = $faker->text, 'subject' => $subject = $faker->text(100)]
        );

        $response->assertJsonFragment([
            'text' => $text
        ]);

        $topic->refresh();

        $this->assertEquals($subject, $topic->subject);
    }

    public function testFailToChangeTopicSubject()
    {
        $faker = Factory::create();
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);

        factory(Post::class)->create(['forum_id' => $this->forum->id, 'topic_id' => $topic->id]);
        $post = factory(Post::class)->create(['user_id' => $this->user->id, 'forum_id' => $this->forum->id, 'topic_id' => $topic->id]);

        $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$post->id}",
            ['text' => $text = $faker->text, 'subject' => $subject = $faker->text(100)]
        );

        $topic->refresh();

        $this->assertNotEquals($subject, $topic->subject);
    }

    public function testEditExistingPostInLockedTopic()
    {
        $faker = Factory::create();
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id, 'is_locked' => true]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$topic->first_post_id}",
            ['text' => $text = $faker->text]
        );

        $response->assertStatus(401);
    }

    public function testEditExistingPostInLockedForum()
    {
        $faker = Factory::create();
        $topic = factory(Topic::class)->create(['forum_id' => $this->forum->id]);

        $this->forum->is_locked = true;
        $this->forum->save();

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit/{$topic->id}/{$topic->first_post_id}",
            ['text' => $text = $faker->text]
        );

        $response->assertStatus(401);
    }

    public function testSubmitTopicWithPoll()
    {
        $faker = Factory::create();

        $response = $this->actingAs($this->user)->json(
            'POST',
            "/Forum/{$this->forum->slug}/Submit",
            [
                'text' => $faker->text,
                'subject' => $faker->text(50),
                'poll' => [
                    'title' => $pollTitle = $faker->word,
                    'max_items' => 1,
                    'length' => 0,
                    'items' => [
                        [
                            'text' => $itemA = $faker->realText(50)
                        ],
                        [
                            'text' => $itemB = $faker->realText(50)
                        ]
                    ]
                ]
            ]
        );

        $response->assertStatus(200);

        $id = $response->json('id');

        $topic = Topic::where('first_post_id', $id)->first();

        $this->assertNotNull($topic->poll_id);

        $this->assertDatabaseHas('polls', ['id' => $topic->poll_id, 'title' => $pollTitle]);
        $this->assertDatabaseHas('poll_items', ['poll_id' => $topic->poll_id, 'text' => $itemA]);
        $this->assertDatabaseHas('poll_items', ['poll_id' => $topic->poll_id, 'text' => $itemB]);
    }
}
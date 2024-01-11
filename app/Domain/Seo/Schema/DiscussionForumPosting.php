<?php
namespace Coyote\Domain\Seo\Schema;

class DiscussionForumPosting implements Thing
{
    public function __construct(
        private string $url,
        private string $title,
        private string $authorUsername,
        private int    $replies)
    {
    }

    public function schema(): array
    {
        return [
            '@context'             => 'https://schema.org',
            '@type'                => 'DiscussionForumPosting',
            '@id'                  => $this->url,
            'headline'             => $this->title,
            'author'               => [
                '@type' => 'Person',
                'name'  => $this->authorUsername,
            ],
            'interactionStatistic' => [
                '@type'                => 'InteractionCounter',
                'userInteractionCount' => $this->replies,
            ],
        ];
    }
}

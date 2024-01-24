<?php
namespace Coyote\Services\Parser\Parsers;

use Coyote\Repositories\Eloquent\WordRepository;
use Illuminate\Support;
use TRegx\SafeRegex\Exception\PregException;
use TRegx\SafeRegex\preg;

class Censore extends HashParser implements Parser
{
    public function __construct(private WordRepository $word)
    {
    }

    protected function parseHashed(string $text): string
    {
        $words = [];
        foreach ($this->censoreRules() as $rule) {
            $wordPattern = str_replace('\*', '(\p{L}*?)', preg_quote($rule->word));
            $word = '#(?<![\p{L}\p{N}_])' . $wordPattern . '(?![\p{L}\p{N}_])#iu';
            $words[$word] = $rule->replacement;
        }
        try {
            $text = preg::replace(array_keys($words), array_values($words), $text);
        } catch (PregException $ignored) {
        }
        return $text;
    }

    private function censoreRules(): Support\Collection
    {
        static $result;
        if ($result === null) {
            $result = $this->word->allWords();
        }
        return $result;
    }
}

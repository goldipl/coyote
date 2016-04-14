<?php

namespace Coyote\Http\Controllers\Tag;

use Coyote\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Coyote\Repositories\Contracts\TagRepositoryInterface as Tag;

/**
 * Class PromptController
 * @package Coyote\Http\Controllers\Tag
 */
class PromptController extends Controller
{
    /**
     * @param Request $request
     * @param Tag $tag
     * @return $this
     * 
     * @todo Do usuniecia. Podobny kod jest w kontrolerach w katalogu ForumController oraz JobController
     */
    public function index(Request $request, Tag $tag)
    {
        $this->validate($request, ['q' => 'required|string|max:25']);
        return view('components.tags')->with('tags', $tag->lookupName(ltrim($request['q'], '#')));
    }

    /**
     * @param Request $request
     * 
     * @todo Do usuniecia poniewaz walidacja tagu powinna odbywac sie w zaleznosci od kontekstu (forum, ofety pracy)
     */
    public function valid(Request $request)
    {
        $this->validate($request, ['t' => 'required|string|max:25|tag|tag_creation:2']);
    }
}

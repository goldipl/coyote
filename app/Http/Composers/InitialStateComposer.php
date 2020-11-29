<?php

namespace Coyote\Http\Composers;

use Coyote\Services\JwtToken;
use Coyote\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InitialStateComposer
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var string
     */
    private $jwtToken;


    /**
     * @param Request $request
     * @param JwtToken $jwtToken
     */
    public function __construct(Request $request, JwtToken $jwtToken)
    {
        $this->request = $request;

        if ($request->user()) {
            $this->jwtToken = $jwtToken->token($request->user());
        }
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $state = array_merge(
            $this->request->attributes->all(),
            [
                'user' => [
                    'notifications_unread' => 0,
                    'pm_unread'     => 0
                ]
            ],
            $this->registerUserModel()
        );

        $view->with('__INITIAL_STATE', json_encode($state));
        $view->with('__WS_URL', $this->websocketUrl());
    }

    /**
     * @return string|null
     */
    private function websocketUrl(): ?string
    {
        if (config('services.ws.host') && $this->request->user()) {
            return sprintf(
                '%s://%s%s/realtime?token=%s',
                $this->request->isSecure() ? 'wss' : 'ws',
                config('services.ws.host'),
                (config('services.ws.port') ? ':' . config('services.ws.port') : ''),
                $this->jwtToken
            );
        }

        return null;
    }

    /**
     * @return array|array[]
     */
    private function registerUserModel(): array
    {
        if (empty($this->request->user())) {
            return [];
        }

        $user = $this->request->user();

        return [
            'user' => [
                'id'                    => $user->id,
                'name'                  => $user->name,
                'date_format'           => $this->mapFormat($user->date_format),
                'token'                 => $this->jwtToken,
                'notifications_unread'  => $user->notifications_unread,
                'pm_unread'             => $user->pm_unread,
                'created_at'            => $user->created_at->toIso8601String(),
                'photo'                 => (string) $user->photo->url(),
                'is_sponsor'            => $user->is_sponsor
            ]
        ];
    }


    /**
     * @param string $format
     * @return string
     */
    private function mapFormat(string $format): string
    {
        $values = [
            'dd-MM-yyyy HH:mm',
            'yyyy-MM-dd HH:mm',
            'MM/dd/yy HH:mm',
            'dd-MM-yy HH:mm',
            'dd MMM yy HH:mm',
            'dd MMMM yyyy HH:mm'
        ];

        return array_combine(array_keys(User::dateFormatList()), $values)[$format];
    }
}

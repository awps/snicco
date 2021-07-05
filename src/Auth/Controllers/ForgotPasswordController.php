<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Controllers;

    use Respect\Validation\Validator;
    use WP_User;
    use BetterWP\Auth\Traits\ResolvesUser;
    use BetterWP\Contracts\ViewInterface;
    use BetterWP\Auth\Mail\ResetPasswordMail;
    use BetterWP\Http\Controller;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Responses\RedirectResponse;
    use BetterWP\Mail\MailBuilder;
    use BetterWP\Session\CsrfField;
    use Respect\Validation\Validator as v;

    class ForgotPasswordController extends Controller
    {

        use ResolvesUser;

        /**
         * @var int
         */
        protected $expiration;

        public function __construct( int $expiration = 300 )
        {
            $this->expiration = $expiration;
        }

        public function create(CsrfField $csrf) : ViewInterface
        {

            return $this->view_factory->make('auth-layout')->with([
                'view' => 'auth-forgot-password',
                'view_factory' => $this->view_factory,
                'csrf_field' => $csrf->asHtml(),
                'post' => $this->url->toRoute('auth.forgot.password'),
            ]);

        }

        public function store(Request $request, MailBuilder $mail) : RedirectResponse
        {

            $validated = $request->validate([
                'login' => v::notEmpty(),
            ]);

            $user = $this->getUserByLogin($validated['login']);

            if ($user instanceof WP_User) {

                $magic_link = $this->generateSignedUrl($user);

                $mail->to($user->user_email)
                     ->send(new ResetPasswordMail($user, $magic_link, $this->expiration));

            }

            return $this->response_factory->redirect()
                                    ->toRoute('auth.forgot.password')
                                    ->with('_password_reset_processed', true);

        }

        private function generateSignedUrl(WP_User $user ) : string
        {

            return $this->url->signedRoute(
                'auth.reset.password',
                ['query' => ['id' => $user->ID ] ],
                $this->expiration,
                true
            );

        }

    }
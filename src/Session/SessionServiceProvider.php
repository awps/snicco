<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Psr\Http\Message\ResponseFactoryInterface;
    use Slim\Csrf\Guard;
    use WPEmerge\Application\Application;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Events\NewLogin;
    use WPEmerge\Session\Events\NewLogout;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Drivers\DatabaseSessionDriver;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Session\Middleware\ShareSessionWithView;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\GlobalContext;

    class SessionServiceProvider extends ServiceProvider
    {

        public function register() : void
        {

            $this->config->extend('session.enabled', false);

            if ( ! $this->config->get('session.enabled')) {
                return;
            }

            $this->bindConfig();
            $this->bindSessionDriver();
            $this->bindSessionManager();
            $this->bindSession();
            $this->bindCsrfMiddleware();
            $this->bindCsrfStore();
            $this->bindSlimGuard();
            $this->bindAliases();
            $this->bindEncryptor();
            $this->bindEvents();

        }

        function bootstrap() : void
        {

            if ( ! $this->config->get('session.enabled')) {
                return;
            }

            $this->bindViewContext();

            $this->bindStatefulRedirector();

        }

        private function bindConfig()
        {

            // misc
            $this->config->extend('session.table', 'sessions');
            $this->config->extend('session.lottery', [2, 100]);
            $this->config->extend('session.driver', 'database');
            $this->config->extend('session.encrypt', false);

            // cookie
            $this->config->extend('session.cookie', 'wp_mvc_session');
            $this->config->extend('session.path', '/');
            $this->config->extend('session.domain', null);
            $this->config->extend('session.secure', true);
            $this->config->extend('session.http_only', true);
            $this->config->extend('session.same_site', 'lax');

            // lifetime
            $this->config->extend('session.lifetime', SessionManager::HOUR_IN_SEC * 8);
            $this->config->extend('session.rotate', $this->config->get('session.lifetime') / 2);

            // middleware
            $this->config->extend('middleware.aliases', [
                'csrf' => CsrfMiddleware::class,
            ]);
            $this->config->extend('middleware.groups.global', [
                StartSessionMiddleware::class,
                ShareSessionWithView::class,
            ]);

        }

        private function bindSession()
        {


            $this->container->singleton(Session::class, function () {

                $store = null;

                if ($this->config->get('session.encrypt')) {

                    $store = new EncryptedSession(
                        $this->container->make(SessionDriver::class),
                        $this->container->make(EncryptorInterface::class)
                    );

                }
                else {

                    $store = new Session($this->container->make(SessionDriver::class));


                }

                $this->container->instance(Session::class, $store);

                return $store;

            });


        }

        private function bindSessionDriver()
        {

            $this->container->singleton(SessionDriver::class, function () {

                $name = $this->config->get('session.driver', 'database');
                $lifetime = $this->config->get('session.lifetime');

                if ($name === 'database') {

                    global $wpdb;

                    $table = $this->config->get('session.table');

                    return new DatabaseSessionDriver($wpdb, $table, $lifetime);

                }

                if ($name === 'array') {
                    return new ArraySessionDriver($lifetime);
                }


            });


        }

        private function bindAliases()
        {

            /** @var Application $app */
            $app = $this->container->make(Application::class);

            $app->alias('session', Session::class);
            $app->alias('csrfField', CsrfField::class, 'asHtml');
            $app->alias('csrf', CsrfField::class);


        }

        private function bindEncryptor()
        {

            $this->container->singleton(EncryptorInterface::class, function () {

                return new Encryptor($this->config->get('app.key'));

            });
        }

        private function bindCsrfMiddleware()
        {

            $this->container->singleton(CsrfMiddleware::class, function ($c, $args) {

                return new CsrfMiddleware(
                    $this->container->make(Guard::class),
                    empty($args) ? '' : Arr::firstEl($args),
                );

            });
        }

        private function bindCsrfStore()
        {

            $this->container->singleton(CsrfStore::class, function () {

                return new CsrfStore($this->container->make(Session::class));

            });
        }

        private function bindSlimGuard()
        {

            $this->container->singleton(Guard::class, function () {

                $storage = $this->container->make(CsrfStore::class);

                return GuardFactory::create(
                    $this->container->make(ResponseFactory::class),
                    $storage
                );

            });
        }

        private function bindEvents()
        {


            if (in_array(AuthServiceProvider::class, $this->config->get('app.providers', []))) {

                return;

            }

            $this->config->extend('events.mapped', [
                'wp_login' => NewLogin::class,
                'wp_logout' => NewLogout::class,
            ]);

            $this->config->extend('events.listeners', [

                NewLogin::class => [

                    [SessionManager::class, 'migrateAfterLogin'],

                ],

                NewLogout::class => [

                    [SessionManager::class, 'invalidateAfterLogout'],

                ],

            ]);


        }

        private function bindSessionManager()
        {

            $this->container->singleton(SessionManager::class, function () {

                return new SessionManager(
                    $this->config->get('session'),
                    $this->container->make(Session::class),
                );


            });

            $this->container->singleton(SessionManagerInterface::class, function () {

                return $this->container->make(SessionManager::class);

            });

        }

        private function bindViewContext()
        {

            /** @var GlobalContext $global_context */
            $global_context = $this->container->make(GlobalContext::class);

            $global_context->add('csrf', $this->container->make(CsrfField::class));
        }

        private function bindStatefulRedirector()
        {

            if ( ! $this->sessionEnabled() ) {

                return;

            }

            $this->container->singleton(AbstractRedirector::class, function () {

                return new StatefulRedirector(
                    $this->container->make(Session::class),
                    $this->container->make(UrlGenerator::class),
                    $this->container->make(ResponseFactoryInterface::class)
                );

            });

        }


    }
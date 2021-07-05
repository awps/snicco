<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\InteractsWithTime;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Http\Cookies;
    use BetterWP\Http\ResponseEmitter;
    use BetterWP\Session\Events\SessionRegenerated;
    use BetterWP\Session\SessionManager;
    use BetterWP\Session\SessionServiceProvider;

    class SessionManagerTest extends TestCase
    {

        use InteractsWithTime;

        /**
         * @var SessionManager
         */
        private $manager;


        public function setUp():void
        {

            $this->afterLoadingConfig(function () {

                $this->withRequest(TestRequest::from('POST', '/wp-login.php'));
                $this->withSessionCookie();

            });

            $this->afterApplicationCreated(function () {

                $this->manager = $this->app->resolve(SessionManager::class);

            });
            parent::setUp();
        }

        public function packageProviders() : array
        {
            return [
                SessionServiceProvider::class
            ];
        }

        private function sentCookies() :Cookies {
            $emitter =  $this->app->resolve(ResponseEmitter::class);
            return $emitter->cookies;
        }

        /** @test */
        public function the_session_id_is_regenerated_on_a_login_event () {

            $this->withDataInSession(['foo' => 'bar']);

            $id_before_login = $this->testSessionId();

            $calvin = $this->createAdmin();
            do_action('wp_login', $calvin->user_login, $calvin);

            $this->assertNoResponse();

            // Session Id not the same
            $this->assertNotSame($new_id = $this->session->getId(), $id_before_login);

            // Session cookie got sent
            $cookies = $this->sentCookies()->toHeaders();
            $this->assertStringContainsString("wp_mvc_session=$new_id", $cookies[0]);

            $this->assertSame($this->session->userId(), $calvin->ID);

            // Driver got updated.
            $this->assertDriverHas('bar', 'foo', $new_id);
            $this->assertDriverEmpty($id_before_login);


        }

        /** @test */
        public function session_are_invalidated_on_logout () {

            $this->withDataInSession(['foo' => 'bar']);

            $calvin = $this->createAdmin();

            $id_before_logout = $this->testSessionId();

            do_action('wp_logout', $calvin->ID);

            // Session Id not the same
            $this->assertNotSame($new_id = $this->session->getId(), $id_before_logout);

            // Session cookie got sent
            $cookies = $this->sentCookies()->toHeaders();
            $this->assertStringContainsString("wp_mvc_session=$new_id", $cookies[0]);

            // Data is for the new id is not in the handler.
            $this->assertDriverNotHas('bar', $new_id);

            $this->assertSame(0, $this->session->userId());

            // The old session is gone.
            $this->assertDriverEmpty($id_before_logout);


        }

        /** @test */
        public function the_provided_user_id_is_set_on_the_session () {

            $this->assertSessionUserId(0);

            $this->manager->start($this->request, 2);

            $this->assertSessionUserId(2);

        }

        /** @test */
        public function initial_session_rotation_is_set () {

            $this->manager->start($this->request, 1);

            $this->assertSame(0, $this->session->rotationDueAt());

            $this->manager->save();

            // 3600 set in fixtures/config/session.php
            $this->assertSame($this->availableAt(3600), $this->session->rotationDueAt());

        }

         /** @test */
        public function absolute_session_timeout_is_set () {

            $this->manager->start($this->request, 1);

            $this->assertSame(0, $this->session->absoluteTimeout());

            $this->manager->save();

            $this->assertSame($this->availableAt(7200), $this->session->absoluteTimeout());

        }

        /** @test */
        public function the_cookie_expiration_is_equal_to_the_max_lifetime () {

            $this->manager->start($this->request, 1);
            $this->manager->save();

            $cookie = $this->manager->sessionCookie()->properties();
            $this->assertSame( $this->availableAt(7200), $cookie['expires']);

        }

        /** @test */
        public function sessions_are_not_rotated_before_the_interval_passes () {

            // Arrange
            $this->withDataInSession(['foo' => 'bar']);

            // Act
            $this->manager->start($this->request, 1);
            $this->manager->save();

            // Assert
            $this->assertSame($this->availableAt(3600), $this->session->rotationDueAt());
            $this->assertSame($this->session->getId(), $this->testSessionId());

            // Arrange
            $this->travelIntoFuture(3599);

            // Act
            $this->manager->save();

            // Assert
            $this->backToPresent();
            $this->assertSame($this->availableAt(3600), $this->session->rotationDueAt());
            $this->assertSame($this->session->getId(), $this->testSessionId());

            // Arrange
            $this->travelIntoFuture(3601);

            // Act
            $this->manager->save();

            // Assert
            $this->assertNotSame($this->session->getId(), $this->getSessionId());

        }

        /** @test */
        public function the_regenerate_session_event_gets_dispatched () {

            ApplicationEvent::fake([SessionRegenerated::class]);

            $this->manager->start($this->request, 1);
            $this->manager->save();

            $this->travelIntoFuture(3601);
            $this->manager->save();
            $this->backToPresent();

            ApplicationEvent::assertDispatched(function (SessionRegenerated $event)  {

                return $event->session === $this->session;

            });

        }

    }
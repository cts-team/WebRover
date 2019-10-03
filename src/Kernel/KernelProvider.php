<?php


namespace WebRover\Framework\Kernel;


use WebRover\Framework\Container\ServiceProvider;
use WebRover\Framework\Kernel\Config\FileLocator;
use WebRover\Framework\Kernel\Controller\ArgumentResolver;
use WebRover\Framework\Kernel\Controller\ControllerResolver;
use WebRover\Framework\Kernel\EventListener\LocaleListener;
use WebRover\Framework\Kernel\EventListener\ResponseListener;
use WebRover\Framework\Kernel\EventListener\RouterListener;
use WebRover\Framework\Kernel\EventListener\SaveSessionListener;
use WebRover\Framework\Kernel\EventListener\SessionListener;
use WebRover\Framework\Kernel\EventListener\StreamedResponseListener;
use WebRover\Framework\Kernel\EventListener\TranslatorListener;
use WebRover\Framework\Kernel\EventListener\ValidateRequestListener;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Util\Misc;

/**
 * Class KernelProvider
 * @package WebRover\Framework\Kernel
 */
class KernelProvider extends ServiceProvider
{
    public function register()
    {
        /**
         * 注册异常处理服务
         */
        $this->app->singleton('whoops', function (Application $app) {
            $whoops = new Run();

            if (Misc::isAjaxRequest()) {
                $handler = new JsonResponseHandler();
                $handler->addTraceToOutput(true);
            } else {
                if (Misc::isCommandLine()) {
                    $handler = new PlainTextHandler();
                } else {
                    $handler = new PrettyPageHandler();
                }
            }

            $whoops->prependHandler($handler);

            $whoops->allowQuit(false);

            $whoops->writeToOutput(false);
            
            return $whoops;
        });

        $this->app->singleton('file_locator', function (Application $app) {
            return new FileLocator($app, $app->getConfigPath(), [$app->getRootPath()]);
        });

        /**
         * 注册路由监听者
         */
        $this->app->singleton(RouterListener::class, function (Application $app) {
            $router = $app->make('router');

            $requestStack = $app->make('request_stack');

            $logger = $app->offsetExists('logger') ? $app->make('logger') : null;

            if (null !== $logger) {
                $logger = $logger->withName('request');
            }

            $debug = $app->isDebug();

            return new RouterListener($router->getMatcher(), $requestStack, null, $logger, $debug);
        });

        /**
         * 注册Locale监听者
         */
        $this->app->singleton(LocaleListener::class, function (Application $app) {

            $requestStack = $app->make('request_stack');

            $translator = $app->make('config')->get('service.translator', []);

            $defaultLocale = isset($translator['locale']) ? $translator['locale'] : 'en';

            $router = $app->make('router');

            return new LocaleListener($requestStack, $defaultLocale, $router);
        });

        /**
         * 注册Translator监听者
         */
        $this->app->singleton(TranslatorListener::class, function (Application $app) {
            $translator = $app->make('translator');

            $requestStack = $app->make('request_stack');

            return new TranslatorListener($translator, $requestStack);
        });

        $this->app->singleton(SessionListener::class, function (Application $app) {
            $session = $app->make('session') ?: null;
            return new SessionListener($session);
        });

        $this->app->singleton(SaveSessionListener::class, function (Application $app) {
            return new SaveSessionListener();
        });

        $this->app->singleton(ValidateRequestListener::class, function (Application $app) {
            return new ValidateRequestListener();
        });

        $this->app->singleton(ResponseListener::class, function (Application $app) {
            return new ResponseListener($app->getCharset());
        });

        $this->app->singleton(StreamedResponseListener::class, function (Application $app) {
            return new StreamedResponseListener();
        });

        /**
         * 注册控制器解析者服务
         */
        $this->app->singleton('controller_resolver', function (Application $app) {
            $logger = $app->offsetExists('logger') ? $app->make('logger') : null;

            return new ControllerResolver($logger);
        });

        /**
         * 注册参数解析者服务
         */
        $this->app->singleton('argument_resolver', function () {
            return new ArgumentResolver();
        });

        /**
         * 注册HTTP核心服务
         */
        $this->app->singleton('http_kernel', function (Application $app) {
            return new HttpKernel(
                $app->make('event'),
                $app->make('controller_resolver'),
                $app->make('argument_resolver'),
                $app->make('request_stack')
            );
        });
    }

    public function boot()
    {
        $app = $this->app;
        $event = $app->make('event');

        $event->addSubscriber($app->make(RouterListener::class));
        $event->addSubscriber($app->make(LocaleListener::class));
        $event->addSubscriber($app->make(TranslatorListener::class));
        $event->addSubscriber($app->make(SessionListener::class));
        $event->addSubscriber($app->make(SaveSessionListener::class));
        $event->addSubscriber($app->make(ValidateRequestListener::class));
        $event->addSubscriber($app->make(ResponseListener::class));
        $event->addSubscriber($app->make(StreamedResponseListener::class));
    }
}

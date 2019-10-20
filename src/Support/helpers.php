<?php

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use WebRover\Framework\Container\Container;
use WebRover\Framework\Kernel\Application;
use WebRover\Framework\Routing\Router;
use WebRover\Framework\Support\HtmlString;

if (!function_exists('app')) {
    /**
     * @return Application
     */
    function app()
    {
        return Container::getInstance()->make('app');
    }
}

if (!function_exists('asset')) {
    /**
     * @param $path
     * @param null $packageName
     * @return mixed
     */
    function asset($path, $packageName = null)
    {
        return app()->make('asset')->getUrl($path, $packageName);
    }
}

if (!function_exists('event')) {
    /**
     * @param $eventName
     * @param Event|null $event
     * @return mixed
     */
    function event($eventName, Event $event = null)
    {
        return app()->make('event')->dispatch($eventName, $event);
    }
}

if (!function_exists('filesystem')) {
    /**
     * @return Filesystem
     */
    function filesystem()
    {
        return app()->make('filesystem');
    }
}

if (!function_exists('request')) {
    /**
     * @return Request
     */
    function request()
    {
        return app()->make('request_stack')->getCurrentRequest();
    }
}

if (!function_exists('router')) {
    /**
     * @return Router
     */
    function router()
    {
        return app()->make('router');
    }
}

if (!function_exists('route')) {
    /**
     * @param $name
     * @param array $parameters
     * @param int $referenceType
     * @return string|null
     */
    function route($name, $parameters = [], $referenceType = Router::ABSOLUTE_PATH)
    {
        return router()->generate($name, $parameters, $referenceType);
    }
}

if (!function_exists('view')) {
    /**
     * @return Environment
     */
    function view()
    {
        return app()->make('view');
    }
}

if (!function_exists('config')) {
    /**
     * @param $key
     * @param null $default
     * @return void|mixed
     */
    function config($key, $default = null)
    {
        $config = app()->make('config');

        if (is_array($key)) {
            $config->set($key);
        } else {
            if (0 === strpos($key, '?')) {
                return $config->has(substr($key, 1));
            }

            return $config->get($key, $default);
        }
    }
}

if (!function_exists('cache')) {
    /**
     * @param $key
     * @param string $value
     * @param int|null $ttl
     * @return mixed
     */
    function cache($key, $value = '', $ttl = null)
    {
        $cache = app()->make('cache');

        if (0 === strpos($key, '?')) {
            return $cache->has(substr($key, 1));
        }

        if (null === $value) {
            return $cache->delete($key);
        }

        if ($value) {
            return $cache->set($key, $value, $ttl);
        } else {
            return $cache->get($key);
        }
    }
}

if (!function_exists('session')) {
    /**
     * @param $key
     * @param string $value
     * @return mixed
     */
    function session($key, $value = '')
    {
        $session = app()->make('session');

        if (0 === strpos($key, '?')) {
            return $session->has(substr($key, 1));
        }

        if (null === $value) {
            return $session->remove($key);
        }

        if ($value) {
            return $session->set($key, $value);
        } else {
            return $session->get($key);
        }
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    function csrf_token()
    {
        return app()->make('session')->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * @return HtmlString
     */
    function csrf_field()
    {
        return new HtmlString('<input type="hidden" name="_token" value="' . csrf_token() . '">');
    }
}

if (!function_exists('event')) {
    /**
     * @param $eventName
     * @param Event|null $event
     * @return Event
     */
    function event($eventName, Event $event = null)
    {
        if (is_object($eventName)) {
            $event = $eventName;
            $r = new ReflectionObject($event);

            $eventName = $r->getName();
        }

        return app()->make('event')->dispatch($eventName, $event);
    }
}

if (!function_exists('trans')) {
    /**
     * @param $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return app()->make('translator')->trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * @param $id
     * @param $number
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    function trans_choice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return app()->make('translator')->transChoice($id, $number, $parameters, $domain, $locale);
    }
}

if (!function_exists('public_path')) {
    /**
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->getPublicPath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('root_path')) {
    /**
     * @param string $path
     * @return string
     */
    function root_path($path = '')
    {
        return app()->getRootPath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->getAppPath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->getConfigPath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return app()->getStoragePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('resource_path')) {
    /**
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        return app()->getResourcePath() . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('public_url')) {
    /**
     * @param string $path
     * @param bool $absolute
     * @return string
     */
    function public_url($path = '', $absolute = true)
    {
        $url = \request()->getBaseUrl();

        if ($absolute) {
            $url = \request()->getSchemeAndHttpHost() . $url;
        }

        return $url . '/' . $path;
    }
}

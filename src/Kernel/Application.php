<?php


namespace WebRover\Framework\Kernel;


use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use WebRover\Framework\Container\Container;
use WebRover\Framework\Container\ServiceProviderInterface;
use WebRover\Framework\Kernel\Bundle\BundleInterface;
use WebRover\Framework\Kernel\EventListener\RouterListener;

/**
 * Class Application
 * @package WebRover\Framework\Kernel
 */
abstract class Application extends Container implements KernelInterface, RebootableInterface, TerminableInterface
{
    /**
     * @var BundleInterface[]
     */
    protected $bundles = [];

    protected $providers = [];

    protected $publicPath;

    protected $rootPath;

    protected $appPath;

    protected $configPath;

    protected $storagePath;

    protected $resourcePath;

    protected $debug;

    protected $environment;

    protected $name;

    protected $charset;

    protected $booted = false;

    protected $startTime;

    private $requestStackSize = 0;

    private $resetServices = false;

    private $appConfig;

    const VERSION = '1.0.1';

    public function __construct($environment, $debug, $publicPath)
    {
        $this->environment = $environment;
        $this->debug = (bool)$debug;
        $this->publicPath = $publicPath;

        $this->resetContainer();

        parent::__construct();
    }

    public function boot()
    {
        $debug = $this->debug;
        if (true === $this->booted) {
            if (!$this->requestStackSize && $this->resetServices) {
                $this->resetContainer();
                $this->resetServices = false;
                if ($debug) {
                    $this->startTime = microtime(true);
                }
            }

            return;
        }

        $this->bootstrap();

        if ($debug) {
            $this->startTime = microtime(true);

            if (!isset($_ENV['SHELL_VERBOSITY']) && !isset($_SERVER['SHELL_VERBOSITY'])) {
                putenv('SHELL_VERBOSITY=3');
                $_ENV['SHELL_VERBOSITY'] = 3;
                $_SERVER['SHELL_VERBOSITY'] = 3;
            }
        }

        $this->registerBasePath();

        $this->parseAppConfig();

        $this->getName();

        $this->registerBundles();

        $this->registerProviders();


        $this->booting();

        foreach ($this->bundles as $bundle) {
            $bundle->boot();
        }

        foreach ($this->providers as $provider) {
            $provider->boot();
        }

        $this->booted = true;

        $this->booted();
    }

    protected function bootstrap()
    {
    }

    protected function booting()
    {
    }

    protected function booted()
    {
    }

    /**
     * 重置服务容器
     */
    public function resetContainer()
    {
        $keys = $this->keys();

        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        $this::setInstance($this);

        $this->singleton('app', $this);
    }

    /**
     * 注册路径
     */
    public function registerBasePath()
    {
        $this->singleton('path.public', $this->getPublicPath());
        $this->singleton('path.root', $this->getRootPath());
        $this->singleton('path.app', $this->getAppPath());
        $this->singleton('path.config', $this->getConfigPath());
        $this->singleton('path.storage', $this->getStoragePath());
        $this->singleton('path.resource', $this->getResourcePath());
    }

    public function getPublicPath()
    {
        return realpath($this->publicPath);
    }

    public function getRootPath()
    {
        if (is_null($this->rootPath)) {
            $this->rootPath = \dirname($this->getPublicPath());
        }

        return $this->rootPath;
    }

    public function getAppPath()
    {
        if (is_null($this->appPath)) {
            $this->appPath = $this->getRootPath() . DIRECTORY_SEPARATOR . 'app';
        }

        return $this->appPath;
    }

    public function getConfigPath()
    {
        if (is_null($this->configPath)) {
            $this->configPath = $this->getRootPath() . DIRECTORY_SEPARATOR . 'config';
        }

        return $this->configPath;
    }

    public function getStoragePath()
    {
        if (is_null($this->storagePath)) {
            $this->storagePath = $this->getRootPath() . DIRECTORY_SEPARATOR . 'storage';
        }

        return $this->storagePath;
    }

    public function getResourcePath()
    {
        if (is_null($this->resourcePath)) {
            $this->resourcePath = $this->getRootPath() . DIRECTORY_SEPARATOR . 'resource';
        }

        return $this->resourcePath;
    }

    public function parseAppConfig()
    {
        $configFile = $this->getConfigPath() . DIRECTORY_SEPARATOR . 'app.php';

        if (!file_exists($configFile)) {
            throw new FileNotFoundException($this->configPath . DIRECTORY_SEPARATOR . 'app.php');
        }

        $appConfig = include $configFile;

        if (!is_array($appConfig)) {
            throw new \InvalidArgumentException('There is no bundle configuration available in the app file');
        }

        $this->appConfig = $appConfig;
    }

    public function getName()
    {
        if (null === $this->name) {
            $this->name = preg_replace('/[^a-zA-Z0-9_]+/', '', basename($this->appPath));
            if (ctype_digit($this->name[0])) {
                $this->name = '_' . $this->name;
            }
        }

        return $this->name;
    }

    /**
     * 注册模块
     */
    public function registerBundles()
    {
        $this->bundles = [];

        if (!isset($this->appConfig['bundle']) || !is_array($this->appConfig['bundle'])) {
            throw new \InvalidArgumentException('There is no bundle configuration available in the app file');
        }

        $registerBundles = $this->appConfig['bundle'];

        foreach ($registerBundles as $registerBundle) {
            if (!class_exists($registerBundle)) {
                throw new \InvalidArgumentException(sprintf('Class "%s" does not exist', $registerBundle));
            }

            $bundle = new $registerBundle;
            if (!$bundle instanceof BundleInterface) {
                throw new \LogicException('Class ' . $registerBundle . ' must is an instance of BundleInterface');
            }

            $name = $bundle->getName();

            if (isset($this->bundles[$name])) {
                if ($this->debug) {
                    throw new \LogicException(sprintf('A "%s" bundle had been declared', $name));
                } else {
                    continue;
                }
            }

            $this->bundles[$name] = $bundle;
        }
    }

    /**
     * 注册服务提供者
     */
    public function registerProviders()
    {
        $this->providers = [];

        $providers = [];

        if (isset($this->appConfig['provider']) && is_array($this->appConfig['provider'])) {
            $providers = array_merge($providers, $this->appConfig['provider']);
        }

        foreach ($this->bundles as $bundle) {
            $registerProviders = $bundle->registerProviders();

            if (!\is_array($registerProviders)) continue;

            $providers = array_merge($providers, $registerProviders);
        }

        foreach ($providers as $providerClass) {

            $identifier = md5($providerClass);

            if (isset($this->providers[$identifier])) continue;

            $provider = new $providerClass($this);

            if (!($provider instanceof ServiceProviderInterface)) {
                throw new \LogicException(sprintf('Provider:  "%s" must is an instance of ServiceProviderInterface', $providerClass));
            }

            $this->providers[$identifier] = $provider;

            $this->providers[$identifier]->register();
        }
    }

    public function shutdown()
    {
        if (false === $this->booted) {
            return;
        }

        $this->booted = false;

        foreach ($this->getBundles() as $bundle) {
            $bundle->shutdown();
        }

        $this->resetContainer();
        $this->requestStackSize = 0;
        $this->resetServices = false;
    }

    public function reboot()
    {
        $this->shutdown();
        $this->boot();
    }

    public function terminate(Request $request, Response $response)
    {
        if (false === $this->booted) {
            return;
        }

        if ($this->getHttpKernel() instanceof TerminableInterface) {
            $this->getHttpKernel()->terminate($request, $response);
        }
    }

    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $this->boot();

        ++$this->requestStackSize;
        $this->resetServices = true;

        try {
            return $this->getHttpKernel()->handle($request, $type, $catch);
        } finally {
            --$this->requestStackSize;
        }
    }

    /**
     * @return HttpKernel
     */
    public function getHttpKernel()
    {
        return $this->make('http_kernel');
    }

    /**
     * {@inheritdoc}
     */
    public function getCharset()
    {
        return 'UTF-8';
    }

    /**
     * @return BundleInterface[]
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    public function getBundle($name)
    {
        if (!isset($this->bundles[$name])) {
            throw new \InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled. Maybe you forgot to add it in the Bundle key of your %s.yml file?', $name, 'app'));
        }
        return $this->bundles[$name];
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function isDebug()
    {
        return $this->debug;
    }

    public function __clone()
    {
        $this->booted = false;
        $this->resetContainer();
        $this->requestStackSize = 0;
        $this->resetServices = false;
    }

    /**
     * Removes comments from a PHP source string.
     *
     * We don't use the PHP php_strip_whitespace() function
     * as we want the content to be readable and well-formatted.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the comments removed
     */
    public static function stripComments($source)
    {
        if (!\function_exists('token_get_all')) {
            return $source;
        }

        $rawChunk = '';
        $output = '';
        $tokens = token_get_all($source);
        $ignoreSpace = false;
        for ($i = 0; isset($tokens[$i]); ++$i) {
            $token = $tokens[$i];
            if (!isset($token[1]) || 'b"' === $token) {
                $rawChunk .= $token;
            } elseif (T_START_HEREDOC === $token[0]) {
                $output .= $rawChunk . $token[1];
                do {
                    $token = $tokens[++$i];
                    $output .= isset($token[1]) && 'b"' !== $token ? $token[1] : $token;
                } while (T_END_HEREDOC !== $token[0]);
                $rawChunk = '';
            } elseif (T_WHITESPACE === $token[0]) {
                if ($ignoreSpace) {
                    $ignoreSpace = false;

                    continue;
                }

                // replace multiple new lines with a single newline
                $rawChunk .= preg_replace(['/\n{2,}/S'], "\n", $token[1]);
            } elseif (\in_array($token[0], [T_COMMENT, T_DOC_COMMENT])) {
                $ignoreSpace = true;
            } else {
                $rawChunk .= $token[1];

                // The PHP-open tag already has a new-line
                if (T_OPEN_TAG === $token[0]) {
                    $ignoreSpace = true;
                }
            }
        }

        $output .= $rawChunk;

        if (\PHP_VERSION_ID >= 70000) {
            // PHP 7 memory manager will not release after token_get_all(), see https://bugs.php.net/70098
            unset($tokens, $rawChunk);
            gc_mem_caches();
        }

        return $output;
    }

    public function serialize()
    {
        return serialize([$this->environment, $this->debug]);
    }

    public function unserialize($data)
    {
        if (\PHP_VERSION_ID >= 70000) {
            list($environment, $debug) = unserialize($data, ['allowed_classes' => false]);
        } else {
            list($environment, $debug) = unserialize($data);
        }

        $this->__construct($environment, $debug, $this->getPublicPath());
    }
}

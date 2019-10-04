<?php


namespace WebRover\Framework\Routing\Loader;


use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class PhpFileLoader
 * @package WebRover\Framework\Routing\Loader
 */
class PhpFileLoader extends FileLoader
{
    private static $availableKeys = [
        'resource', 'type', 'prefix', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options', 'condition', 'controller',
    ];

    /**
     * Loads a file.
     *
     * @param string $file A file path
     * @param string|null $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When a route can't be parsed because is invalid
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        $prevErrorHandler = set_error_handler(function ($level, $message, $script, $line) use ($file, &$prevErrorHandler) {
            $message = E_USER_DEPRECATED === $level ? preg_replace('/ on line \d+/', ' in "' . $file . '"$0', $message) : $message;

            return $prevErrorHandler ? $prevErrorHandler($level, $message, $script, $line) : false;
        });

        $parsedConfig = include $path;

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $parsedConfig) {
            return $collection;
        }

        // not an array
        if (!\is_array($parsedConfig)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a array.', $path));
        }

        foreach ($parsedConfig as $name => $config) {
            $this->validate($config, $name, $path);

            if (isset($config['resource'])) {
                $this->parseImport($collection, $config, $path, $file);
            } else {
                $this->parseRoute($collection, $name, $config, $path);
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && \in_array(pathinfo($resource, PATHINFO_EXTENSION), ['php'], true) && (!$type || 'php' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param string $name Route name
     * @param array $config Route definition
     * @param string $path Full path of the file being processed
     */
    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        $defaults = isset($config['defaults']) ? $config['defaults'] : [];
        $requirements = isset($config['requirements']) ? $config['requirements'] : [];
        $options = isset($config['options']) ? $config['options'] : [];
        $host = isset($config['host']) ? $config['host'] : '';
        $schemes = isset($config['schemes']) ? $config['schemes'] : [];
        $methods = isset($config['methods']) ? $config['methods'] : [];
        $condition = isset($config['condition']) ? $config['condition'] : null;

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }

        $route = new Route($config['path'], $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

        $collection->add($name, $route);
    }

    /**
     * Parses an import and adds the routes in the resource to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param array $config Route definition
     * @param string $path Full path of the file being processed
     * @param string $file Loaded file name
     */
    protected function parseImport(RouteCollection $collection, array $config, $path, $file)
    {
        $type = isset($config['type']) ? $config['type'] : null;
        $prefix = isset($config['prefix']) ? $config['prefix'] : '';
        $defaults = isset($config['defaults']) ? $config['defaults'] : [];
        $requirements = isset($config['requirements']) ? $config['requirements'] : [];
        $options = isset($config['options']) ? $config['options'] : [];
        $host = isset($config['host']) ? $config['host'] : null;
        $condition = isset($config['condition']) ? $config['condition'] : null;
        $schemes = isset($config['schemes']) ? $config['schemes'] : null;
        $methods = isset($config['methods']) ? $config['methods'] : null;

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }

        $this->setCurrentDir(\dirname($path));

        $imported = $this->import($config['resource'], $type, false, $file);

        if (!\is_array($imported)) {
            $imported = [$imported];
        }

        foreach ($imported as $subCollection) {
            /* @var $subCollection RouteCollection */
            $subCollection->addPrefix($prefix);
            if (null !== $host) {
                $subCollection->setHost($host);
            }
            if (null !== $condition) {
                $subCollection->setCondition($condition);
            }
            if (null !== $schemes) {
                $subCollection->setSchemes($schemes);
            }
            if (null !== $methods) {
                $subCollection->setMethods($methods);
            }
            $subCollection->addDefaults($defaults);
            $subCollection->addRequirements($requirements);
            $subCollection->addOptions($options);

            $collection->addCollection($subCollection);
        }
    }

    /**
     * Validates the route configuration.
     *
     * @param array $config A resource config
     * @param string $name The config key
     * @param string $path The loaded file path
     *
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    protected function validate($config, $name, $path)
    {
        if (!\is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The definition of "%s" in "%s" must be a array.', $name, $path));
        }
        if ($extraKeys = array_diff(array_keys($config), self::$availableKeys)) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" contains unsupported keys for "%s": "%s". Expected one of: "%s".', $path, $name, implode('", "', $extraKeys), implode('", "', self::$availableKeys)));
        }
        if (isset($config['resource']) && isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "resource" key and the "path" key for "%s". Choose between an import and a route definition.', $path, $name));
        }
        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf('The "type" key for the route definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.', $name, $path));
        }
        if (!isset($config['resource']) && !isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('You must define a "path" for the route "%s" in file "%s".', $name, $path));
        }
        if (isset($config['controller']) && isset($config['defaults']['_controller'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "controller" key and the defaults key "_controller" for "%s".', $path, $name));
        }
    }
}

<?php


namespace WebRover\Framework\Asset;


use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use WebRover\Framework\Container\ServiceProvider;
use WebRover\Framework\Kernel\Application;

/**
 * Class AssetProvider
 * @package WebRover\Framework\Asset
 */
class AssetProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('asset', function (Application $app) {

            $params = $app
                ->make('config')
                ->get('service.asset', []);

            $defaultPackage = $this->parsePackage($params);

            $namedPackages = [];

            if (isset($params['packages']) && \is_array($params['packages'])) {

                foreach ($params['packages'] as $pkgName => $pkgParams) {
                    if (!\is_array($pkgParams)) continue;

                    $namedPackages[$pkgName] = $this->parsePackage($pkgParams);
                }
            }

            return new Packages($defaultPackage, $namedPackages);

        });
    }

    private function parsePackage(array $params)
    {
        if (!isset($params['version'])) $params['version'] = '';
        $params['version'] = trim($params['version']);

        if (!$params['version']) {
            $strategy = new EmptyVersionStrategy();
        } else {
            if (!isset($params['version_format'])) $params['version_format'] = null;
            $params['version_format'] = trim($params['version_format']);

            $strategy = new StaticVersionStrategy($params['version'], $params['version_format']);
        }

        if (isset($params['base_urls'])) {

            if (!is_array($params['base_urls'])) {
                $params['base_urls'] = [$params['base_urls']];
            }

            if (isset($params['base_path']) && trim($params['base_path'])) {
                $params['base_path'] = trim($params['base_path']);

                foreach ($params['base_urls'] as &$base_url) {
                    $base_url .= $base_url;
                }
            }

            $package = new UrlPackage($params['base_urls'], $strategy);
        } else {
            if (isset($params['base_path']) && trim($params['base_path'])) {
                $package = new PathPackage($params['base_path'], $strategy);
            } else {
                $requestStack = $this->app->make('request_stack');

                $requestStackContext = new RequestStackContext($requestStack);
                $package = new PathPackage('/', $strategy, $requestStackContext);
            }
        }

        return $package;
    }
}

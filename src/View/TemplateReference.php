<?php


namespace WebRover\Framework\View;

use Symfony\Component\Templating\TemplateReference as BaseTemplateReference;

/**
 * Class TemplateReference
 * @package WebRover\Framework\View
 */
class TemplateReference extends BaseTemplateReference
{
    public function __construct($bundle = null, $controller = null, $name = null, $engine = null)
    {
        $this->parameters = [
            'bundle' => $bundle,
            'controller' => $controller,
            'name' => $name,
            'engine' => $engine,
        ];
    }


    /**
     * Returns the path to the template
     *  - as a path when the template is not part of a bundle
     *  - as a resource when the template is part of a bundle.
     *
     * @return string A path to the template or a resource
     */
    public function getPath()
    {
        $controller = str_replace('\\', '/', $this->get('controller'));

        $path = (empty($controller) ? '' : $controller . '/') . $this->get('name') . '.' . $this->get('engine');

        return empty($this->parameters['bundle']) ? $path : '@' . $this->get('bundle') . '/resource/view/' . $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogicalName()
    {
        return sprintf('%s:%s:%s.%s', $this->parameters['bundle'], $this->parameters['controller'], $this->parameters['name'], $this->parameters['engine']);
    }
}
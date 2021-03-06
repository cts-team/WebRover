<?php


namespace WebRover\Framework\Kernel\Controller;


/**
 * Acts as a marker and a data holder for a Controller.
 *
 * Some methods in Symfony accept both a URI (as a string) or a controller as
 * an argument. In the latter case, instead of passing an array representing
 * the controller, you can use an instance of this class.
 *
 * Class ControllerReference
 * @package WebRover\Framework\Kernel\Controller
 */
class ControllerReference
{
    public $controller;
    public $attributes = [];
    public $query = [];

    /**
     * @param string $controller The controller name
     * @param array $attributes An array of parameters to add to the Request attributes
     * @param array $query An array of parameters to add to the Request query string
     */
    public function __construct($controller, array $attributes = [], array $query = [])
    {
        $this->controller = $controller;
        $this->attributes = $attributes;
        $this->query = $query;
    }
}
<?php


namespace WebRover\Framework\Validation;


/**
 * Class Validator
 * @package WebRover\Framework\Validation
 */
abstract class Validator
{
    protected $data = [];

    protected $optional = [];

    protected $alias = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;

        $this->validate();
    }

    public function getData()
    {
        return $this->data;
    }

    private function validate()
    {
        $obj = new \ReflectionObject($this);
        $methods = $obj->getMethods(\ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isDestructor() || $method->isStatic() || $method->isAbstract()) {
                continue;
            }

            $name = $var = $method->getName();

            if (isset($this->alias[$name])) {
                $var = $this->alias[$name];
            }

            $r = $this->$name($this->data[$var]);

            $this->data[$var] = $r;
        }
    }
}

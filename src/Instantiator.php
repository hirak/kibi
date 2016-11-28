<?php
/**
 * hirak/kibi DI Container
 * @author Hiraku NAKANO
 * @license MIT https://github.com/hirak/kibi/blob/master/LICENSE.md
 */
namespace Hirak\Kibi;

use ReflectionClass;
use ReflectionMethod;

class Instantiator
{
    private $compiledMaker;
    private $runtimeMaker;

    private $compiledMakerPath;

    private $contexts = [];

    public function __construct(array $config = [])
    {
        $config += [
            'cache' => false,
        ];

        if ($config['cache']) {
            $this->compiledMakerPath = $config['cache'];
            if (!is_writable($config['cache'])) {
                throw new Exception\FilesystemError("{$config['cache']} is not writable.");
            }
            if (file_exists($config['cache'])) {
                $this->compiledMaker = require $config['cache'];
            }
        }

        $this->runtimeMaker = new RuntimeMaker;
    }

    public function addTrait(string $traitName)
    {
        if (!trait_exists($traitName)) {
            throw new Exception\InvalidArgumentError("trait: $traitName is not defined.");
        }

        if ($this->runtimeMaker->hasTrait($traitName)) {
            return;
        }

        $instance = eval('return new class { use ' . $traitName . '; };');
        $rc = new ReflectionClass($traitName);
        $nsName = $rc->getNamespaceName();
        foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
            $methodName = $rm->name;
            if (0 === strncmp('get__', $methodName, 5)) {
                $this->runtimeMaker->addNamespace($nsName);
                $this->runtimeMaker->addTrait($traitName);

                $retType = $rm->getReturnType();
                $name = substr($methodName, 5);
                if (ctype_digit($name)) {
                    $name = '';
                }
                $this->runtimeMaker->addFactory($nsName, (string)$retType, $name, $traitName, $instance);
            }
        }
    }

    public function get(string $id)
    {
        if (false === strpos($id, '#')) {
            $id .= '#';
        }
        list($class, $name) = explode('#', $id);
        $namespace = '';
        if (false !== strpos($class, '\\')) {
            $normalized = strtr($class, '\\', '/');
            $namespace = strtr(dirname($normalized), '/', '\\');
        }
        return $this->runtimeMaker->generate($namespace, $class, $name);
    }
}

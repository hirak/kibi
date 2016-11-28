<?php
namespace Hirak\Kibi;

use ReflectionClass;

class RuntimeMaker
{
    private $namespaces = [];
    private $traits = [];
    private $factories = [];

    public function addNamespace(string $namespace)
    {
        $this->namespaces[$namespace] = true;
    }

    public function addTrait(string $trait)
    {
        $this->traits[$trait] = true;
    }

    public function hasTrait(string $trait)
    {
        return isset($this->traits[$trait]);
    }

    /**
     * @param $namespace ex: Acme\Special
     * @param $type      ex: string, int, Acme\Special\Klass
     * @param $name      exclude 'get' prefix
     * @param $trait
     * @param object $instance
     */
    public function addFactory(string $namespace, string $type, string $name, string $trait, $instance)
    {
        $key = strtr($namespace, '\\', '_') . '__' . strtr($type, '\\', '_') . '__' . $name;
        $count = 0;
        // すでに登録されている場合は何もしない
        if (!isset($this->factories[$key])) {
            $this->factories[$key] = compact('instance', 'trait', 'name', 'count');
        }
    }

    /**
     * @return mixed
     */
    public function generate(string $namespace, string $type, string $name)
    {
        $normalizedtype = strtr($type, '\\', '_');
        $keys = [];
        foreach ($this->namespaces as $ns => $_) {
            if (0 === strncmp($ns, $namespace, strlen($ns))) {
                $normalizedns = strtr($ns, '\\', '_');
                $keys[] = "{$normalizedns}__{$normalizedtype}__{$name}";
                $keys[] = "{$normalizedns}__{$normalizedtype}__";
                $keys[] = "{$normalizedns}____{$name}";
            }
        }
        $keys[] = "__{$normalizedtype}__{$name}";
        $keys[] = "__{$normalizedtype}__";
        $keys[] = "____{$name}";
        
        foreach ($keys as $key) {
            if (isset($this->factories[$key])) {
                $factory = $this->factories[$key];
                $response = $factory['instance']->{"get__$factory[name]"}();

                if (0 === $factory['count']) {
                    // 返却できるものが他にあるとしたらそれも追加しておく
                    if (is_object($return)) {
                        foreach (class_implements($return) as $interface) {
                            $this->addFactory($namespace, $interface, $name, $factory['trait'], $factory['instance']);
                        }
                        foreach (class_parents($return) as $class) {
                            $this->addFactory($namespace, $class, $name, $factory['trait'], $factory['instance']);
                        }
                    }
                    ++$factory['count'];
                }
                return $response;
            }
        }

        // ここから先はルールが見つからなかったときの処理
        if (!class_exists($type)) {
            // typeがクラス名じゃないならお手上げ
            throw new Exception\CannotInstantiateError("$type cannot instantiate.");
        }

        // クラスの場合は、とりあえずそのクラスをインスタンス化しようとしてみる
        $rc = new ReflectionClass($type);
        $constr = $rc->getConstructor();
        if (!$constr) {
            return $rc->newInstance();
        }

        $params = [];
        foreach ($constr->getParameters() as $param) {
            if ($param->hasType()) {
                $params[] = $this->generate($namespace, (string)$param->getType(), $param->name);
            } else {
                $params[] = $this->generate($namespace, '', $param->name);
            }
        }

        return $rc->newInstanceArgs($params);
    }
}

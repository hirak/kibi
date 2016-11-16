<?php
/**
 * hirak/kibi DI Container
 * @author Hiraku NAKANO
 * @license MIT https://github.com/hirak/kibi/blob/master/LICENSE.md
 */
namespace Hirak\Kibi;

use ReflectionClass;

class Instantiator
{
    private $cache;

    public function __construct(array $config = [])
    {
        $config += [
            'strict' => false,
            'cache' => false,
        ];
        if ($config['strict']) {
            $stacktrace = debug_backtrace(0, 2);
            if (count($stacktrace) > 1) {
                throw new \LogicException('You must invoke Instantiator at top level.');
            }
        }
    }

    /**
     * @param string $id
     */
    public function generateByClass(string $className)
    {
        $rc = new ReflectionClass($className);
        $constr = $rc->getConstructor();
        if (!$constr) {
            return $rc->newInstance();
        }

        $params = [];
        foreach ($constr->getParameters() as $param) {
            if (!$param->hasType()) {
                //今のところ これに対処する手段はない
                throw new \RuntimeException($param->name . ' don\'t have any decralations.');
            }
            $type = $param->getType();
            if ($type->isBuiltin()) {
                //今のところ これに対処する手段はない
                throw new \RuntimeException("$type cannot instantiate");
            }
            $params[] = $this->generateByClass($type);
        }

        return $rc->newInstanceArgs($params);
    }
}

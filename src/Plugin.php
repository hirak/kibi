<?php
/**
 * hirak/kibi DI Container
 * @author Hiraku NAKANO
 * @license MIT https://github.com/hirak/kibi/blob/master/LICENSE.md
 */
namespace Hirak\Kibi;

use Composer\{
    Composer,
    IO\IOInterface,
    Plugin\PluginInterface
};

class Plugin implements PluginInterface
{
    private $composer;
    private $io;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }
}

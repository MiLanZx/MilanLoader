<?php

namespace DevMilanz\MilanLoader;

use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;

class PluginZIPLoader implements PluginLoader
{
    private \DynamicClassLoader $loader;

    public function __construct(\DynamicClassLoader $loader)
    {
        $this->loader = $loader;
    }

    public function canLoadPlugin(string $path): bool
    {

        return !is_null($this->findPluginYml($path));
    }

    public function loadPlugin(string $file): void
    {
        var_dump($file);
        $name = explode("/", $file);
        $name = explode(".", end($name));
        $name = reset($name);
        $realPath = $this->getRealPathYaml($file);
        $pathTo = is_null($realPath) ? Server::getInstance()->getDataPath() . "/plugins/$name" : Server::getInstance()->getDataPath() . "/plugins";
        MilanLoader::getInstance()->unZip($file, $pathTo);
        $description = $this->getPluginDescription($file);
        unlink($file);
        if (!is_null($description)) {
            $this->loader->addPath($description->getSrcNamespacePrefix(), Server::getInstance()->getDataPath() . "/plugins/$name/src");
            MilanLoader::getInstance()->addPlugin($name, $description->getName(), realpath(""));
        }
    }

    public function getPluginDescription(string $file): ?PluginDescription
    {
        $zip = new \ZipArchive;
        if ($zip->open($file) === true) {
            $yaml = $zip->getFromName($this->findPluginYml($file));
            if ($yaml != false) return new PluginDescription($yaml);
            $zip->close();
        }
        return null;
    }

    public function getAccessProtocol(): string
    {
        return "";
    }

    public function getRealPathYaml(string $file): ?string
    {
        $return = null;
        $zip = new \ZipArchive;
        if ($zip->open($file) === true) {
            if ($zip->getFromName("plugin.yml") === false) {
                $return = realpath($this->findPluginYml($file));
            }
            $zip->close();
        }
        return $return;
    }

    public function findPluginYml(string $file): ?string
    {
        $zip = new \ZipArchive;
        if ($zip->open($file) === true) {
            $index = 0;
            $name = "";
            while (!in_array("plugin.yml", explode("/", $name))) {
                if ($index > $zip->count()) return null;
                $name = $zip->statIndex($index)["name"] ?? "";
                $index++;
            }
            $zip->close();
            return $name;
        } else return null;
    }
}
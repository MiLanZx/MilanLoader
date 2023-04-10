<?php

namespace DevMilanz\MilanLoader;

use pocketmine\plugin\PharPluginLoader;
use pocketmine\plugin\PluginBase;

class MilanLoader extends PluginBase
{
    private static self $this;
    private array $plugins = [];

    public function onLoad(): void
    {
        self::$this = $this;
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerInterface(new PluginZIPLoader($this->getServer()->getLoader()));
    }

    public function onEnable(): void
    {
        foreach ($this->plugins as $name => $value) {
            //                if (!is_dir($configFolder . "/" . $config)) {
            //                    unlink($configFolder . "/" . $config);
            //                    $realPath = $value["realPath"];
            //                    $baseResources = $this->getServer()->getDataPath() . "plugins/{$realPath}resources/$config";
            //                    $baseResources = str_replace("//", "/", $baseResources);
            //                    //if (is_dir($baseResources)) {
            //                    //                        $this->duplicateFolder($baseResources, $configFolder . "/" . $config);
            //                    //                    } else copy($baseResources, $configFolder . "/" . $config);
            //
            //                    $file = fopen($baseResources, "r");
            //                    fwrite($file, file_get_contents($configFolder . "/" . $config));
            //                    fclose($file);
            //                }
            //            } #Gak tau Mau Ngapain


            $configFolder = str_replace("//", "/", $this->getServer()->getDataPath() . "/plugins/{$name}/");
            $configs = scandir($configFolder);
            foreach ($configs as $number => $config) {
                if (!is_dir($configFolder . $config)) {
                    unlink($configFolder . $config);
                } else $this->duplicateFolder($configFolder . $config, "");
            }
        }
    }

    public function addPlugin(string $folderName, string $name, string $realPath): void
    {
        $this->plugins[$name] = ["folderName" => $folderName, "realPath" => $realPath];
    }

    public function onDisable(): void
    {
        foreach ($this->plugins as $name => $value) {
            $plugin = $this->getServer()->getPluginManager()->getPlugin($name);
            if (!is_null($plugin)) $this->getServer()->getPluginManager()->disablePlugin($plugin);
            $folderName = $value["folderName"];
            $dir = $this->getServer()->getDataPath() . "/plugins/$folderName/";
            if (is_dir($dir)) {
                $this->zip($dir, $this->getServer()->getDataPath() . "/plugins/$name.zip");
                $this->removeDir($dir);
            }
        }
    }

    public static function getInstance(): self
    {
        return self::$this;
    }

    public function zip(string $source, string $destination): bool
    {
        if (!extension_loaded('zip') || !file_exists($source)) return false;
        if (file_exists($destination)) $this->removeDir($destination);

        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZipArchive::CREATE)) return false;
        $source = str_replace('\\', '/', realpath($source));
        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $file = str_replace('\\', '/', $file);
                if (in_array(substr($file, strrpos($file, '/') + 1), array('.', '..'))) continue;

                $file = realpath($file);
                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } elseif (is_file($file) === true) $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
            }
        } elseif (is_file($source) === true) $zip->addFromString(basename($source), file_get_contents($source));

        return $zip->close();
    }

    public function unZip(string $path, string $pathTo): ?string
    {
        $zip = new \ZipArchive;
        if ($zip->open($path)) {
            $zip->extractTo($pathTo);
            $zip->close();
        }
        return null;
    }

    public function removeDir(string $path): void
    {
        $dir_content = scandir($path);
        if ($dir_content) {
            foreach ($dir_content as $entry) {
                if (!in_array($entry, array('.', '..'))) {
                    $entry = $path . '/' . $entry;
                    if (!is_dir($entry)) {
                        unlink($entry);
                    } else $this->removeDir($entry);
                }
            }
        }
        rmdir($path);
    }

    public function duplicateFolder(string $origin, string $destination): int
    {
        $dossier = opendir($origin);
        if (file_exists($destination)) return 0;
        mkdir($destination, fileperms($origin));
        $total = 0;
        while ($file = readdir($dossier)) {
            $l = array('.', '..');
            if (!in_array($file, $l)) {
                if (is_dir($origin . "/" . $file)) {
                    $total += $this->duplicateFolder("$origin/$file", "$destination/$file");
                } else {
                    copy("$origin/$file", "$destination/$file");
                    $total++;
                }
            }
        }
        return $total;
    }
}

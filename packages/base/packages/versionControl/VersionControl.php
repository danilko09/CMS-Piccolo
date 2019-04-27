<?php

namespace danilko09\packages;

final class VersionControl {

    public static function getCurrentVersion(string $package): string {
        $dataPath = self::getPathForPackage($package);
        return file_exists($dataPath) ? file_get_contents($dataPath) : "";
    }

    public static function isCurrentLower(string $package, string $version): bool {
        return version_compare(self::getCurrentVersion($package), $version, "<");
    }

    public static function isCurrentHiger(string $package, string $version): bool {
        return version_compare(self::getCurrentVersion($package), $version, ">");
    }

    public static function isCurrentEqual(string $package, string $version): bool {
        return version_compare(self::getCurrentVersion($package), $version, "=");
    }

    public static function isVersionUndefined(string $package): bool {
        $dataPath = self::getPathForPackage($package);
        return !file_exists($dataPath) || self::getCurrentVersion($package) == "";
    }

    public static function setCurrentVersion(string $package, string $version) {
        $dataPath = self::getPathForPackage($package);

        $dir = dirname($dataPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dataPath, $version);
    }

    private static function getPathForPackage($package, $suffix = 'installed') {
        $packPath = str_replace("/", DIRECTORY_SEPARATOR, $package);
        return PICCOLO_DATA_DIR . DIRECTORY_SEPARATOR . 'VersionControl'
                . DIRECTORY_SEPARATOR . $packPath . '.' . $suffix;
    }

}

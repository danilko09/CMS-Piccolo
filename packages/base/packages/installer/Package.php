<?php

namespace danilko09\packages;

class Package {

    private $info;
    private $requires;
    private $version;
    private $beforeInstall;
    private $rootFiles;
    private $configs;
    private $locales;
    private $templates;
    private $classes;
    private $scripts;
    private $afterInstall;

    public function __construct($info) {
        $this->info = $info;

        $this->requires = $info['requires'] ?? [];
        $this->version = $info['version'] ?? "";
        $this->beforeInstall = $info['before_install'] ?? null;
        $this->rootFiles = $info['root'] ?? [];
        $this->configs = $info['configs'] ?? [];
        $this->locales = $info['locales'] ?? [];
        $this->templates = $info['templates'] ?? [];
        $this->classes = $info['classes'] ?? [];
        $this->scripts = $info['scripts'] ?? [];
        $this->afterInstall = $info['after_install'] ?? null;
    }

    public function getRequires() {
        return $this->requires;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getDependenciesList() {
        return $this->getRequires();
    }

    public function getBeforeInstall() {
        return $this->beforeInstall;
    }

    public function getRootFiles() {
        return $this->rootFiles;
    }

    public function getConfigs() {
        return $this->configs;
    }

    public function getLocales() {
        return $this->locales;
    }

    public function getTemplates() {
        return $this->templates;
    }

    public function getClasses() {
        return $this->classes;
    }

    public function getScripts() {
        return $this->scripts;
    }

    public function getAfterInstall() {
        return $this->afterInstall;
    }

}

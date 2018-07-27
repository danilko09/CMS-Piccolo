<?php

namespace tests\VersionControl;

loadPackageClasses("base/packages/versionControl");

use danilko09\packages\VersionControl;

testLog("Проверка версии по умолчанию");
assertEquals(VersionControl::getCurrentVersion("test"), "");
assertFalse(VersionControl::isCurrentHiger("test", "1.0"), "Версия по умолчанию должна быть не выше 1.0");
assertTrue(VersionControl::isCurrentLower("test", "1.0"), "Версия по умолчанию должна быть ниже 1.0");
assertTrue(VersionControl::isVersionUndefined("test"), "Версия должна быть не задана");

testLog("setVersion + getVersion + isUndefined");
VersionControl::setCurrentVersion("test", "1.0");
assertFalse(VersionControl::isVersionUndefined("test"), "Версия должна быть задана");
assertEquals(VersionControl::getCurrentVersion("test"), "1.0");

testLog("isCurrentEqual");
assertTrue(VersionControl::isCurrentEqual("test", "1.0"), "Текущая версия должна быть 1.0");
assertFalse(VersionControl::isCurrentEqual("test", "1.1"), "Текущая версия не должна быть 1.1");

testLog("equal by higer/lower");
assertFalse(VersionControl::isCurrentHiger("test", "1.0"), "Текущая версия должна быть не выше 1.0");
assertFalse(VersionControl::isCurrentLower("test", "1.0"), "Текущая версия должна быть не ниже 1.0");

testLog("isCurrentHiger");
assertFalse(VersionControl::isCurrentHiger("test", "1.1"), "Текущая версия должна быть не выше 1.1");
assertTrue(VersionControl::isCurrentHiger("test", "0.1"), "Текущая версия должна быть не ниже 0.1");

testLog("isCurrentLower");
assertFalse(VersionControl::isCurrentLower("test", "0.1"), "Текущая версия не должна быть ниже 0.1");
assertTrue(VersionControl::isCurrentLower("test", "1.1"), "Текущая версия должна быть ниже 1.1");

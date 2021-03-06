Changelog
=========

* 1.0.0-beta2 (2015-04-13)

 * fixed `SymlinkInstaller` when called twice for a mapping to the install 
   target root /
 * renamed `WebResourcePlugin` to `AssetPlugin`
 * renamed `WebPathMapping` to `AssetMapping`
 * renamed `WebPathManager` to `AssetManager`
 * moved all code to `Puli\AssetPlugin` namespace
 * renamed `puli web` command to `puli asset` and `puli web add` to `puli asset map`
 * renamed `PuliWebFactory` to `UrlGeneratorFactory` and removed the parent
   interface `PuliFactory`
 * renamed binding type "puli/web-resource" to "puli/asset-mapping"
 * renamed `ResourceUrlGenerator` to `AssetUrlGenerator`
 * added `IGNORE_TARGET_NOT_FOUND` flag to suppress sanity check in `AssetManager::addAssetMapping()`
 * added `--force` option to `puli asset map` command
 * the `puli asset map` command now supports relative paths
 * fixed `puli asset` command when asset mappings have a non-existing target
 * renamed binding type "puli/asset-mapping" to "puli/asset"
 * removed `$code` arguments of static exception factory methods
 * added `puli asset update` command
 * added `OVERRIDE` flag to `AssetManager`
 * added `AssetManager::removeAssetMappings()`
 * added `AssetManager::clearAssetMappings()`
 * added `InstallerManager::removeInstallerDescriptors()`
 * added `InstallerManager::clearInstallerDescriptors()`
 * added optional argument `$expr` to `InstallerManager::hasInstallerDescriptors()`
 * added `AssetManager` methods for manipulating and accessing root mappings
 * added `InstallerManager` methods for manipulating and accessing root mappings
 * added `InstallerManager::findInstallerDescriptors()`
 * added `InstallTargetManager::removeTargets()`
 * added `InstallTargetManager::clearTargets()`
 * added `InstallTargetManager::findTargets()`
 * added optional argument `$expr` to `InstallTargetManager::hasTargets()`
 * added `puli target update` command
 
* 1.0.0-beta (2015-03-19)

 * first beta release

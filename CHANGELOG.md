Changelog
=========

* 1.0.0-next (@release_date@)

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
 
* 1.0.0-beta (2015-03-19)

 * first beta release

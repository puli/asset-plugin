Changelog
=========

* 1.0.0-next (@release_date@)

 * fixed `SymlinkInstaller` when called twice for a mapping to the install 
   target root /
 * renamed `WebResourcePlugin` to `AssetPlugin`
 * renamed `WebPathMapping` to `AssetMapping`
 * renamed `WebPathManager` to `AssetManager`
 * moved all code to `Puli\AssetPlugin` namespace
 * renamed "web" command to "asset" and "web add" to "asset map"
 * renamed `PuliWebFactory` to `UrlGeneratorFactory` and removed the parent
   interface `PuliFactory`
 
* 1.0.0-beta (2015-03-19)

 * first beta release

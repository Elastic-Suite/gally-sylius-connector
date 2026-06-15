# Gally Plugin for Sylius

## Requirements

- Gally version: 2.1.x
- Sylius version: 2.0.x

## Usage

- Install the `gally/sylius-plugin` bundle 
  - Run `composer require gally/sylius-plugin`
  - Add the bundle in `config/bundles.php`. You must put it after `SyliusGridBundle`
    ```php
    [...]
    Gally\SyliusPlugin\GallySyliusPlugin::class => ['all' => true],
    ```
  - Import the Gally Sylius bundle configuration by adding the following lines to the imports section of `config/packages/_sylius.yaml`
    ```yaml
    - { resource: "@GallySyliusPlugin/Resources/config/config.yml" }
    ```
  - Import admin routes by creating a file `config/routes/gally_admin.yaml`
    ```yaml
    gally_admin:
        resource: "@GallySyliusPlugin/Resources/config/admin_routing.yml"
        prefix: /admin
    ```
  - Import shop routes by creating a file `config/routes/gally_shop.yaml`
    ```yaml
    gally_shop:
        resource: "@GallySyliusPlugin/Resources/config/shop_routing.yml"
        prefix: /{_locale}
    ```
   - Implement the `Gally\SyliusPlugin\Model\GallyChannelInterface` and `Gally\SyliusPlugin\Model\GallyChannelTrait` in your Channel Entity `src/App/Entity/Channel/Channel.php`.
    ```php
    <?php
    
    declare(strict_types=1);
    
    namespace App\Entity\Channel;
    
    use Doctrine\ORM\Mapping as ORM;
    use Gally\SyliusPlugin\Model\GallyChannelInterface;
    use Gally\SyliusPlugin\Model\GallyChannelTrait;
    use Sylius\Component\Core\Model\Channel as BaseChannel;
    
     #[ORM\Entity]
     #[ORM\Table(name: 'sylius_channel')]
     class Channel extends BaseChannel implements GallyChannelInterface
     {
        use GallyChannelTrait;
     }
     ```
    - Copy the bundle assets (Javascript & CSS files):
       - Run `php bin/console assets:install`
       - Run `php bin/console sylius:install:assets`
       - Run `php bin/console sylius:theme:assets:install`

    - **Alternative: install assets via Webpack Encore (recommended for Sylius 2.x)**
      - Add the plugin and its JS SDK as npm dependencies in your app's `package.json`:
        ```json
        {
          "dependencies": {
            "@gally/sylius-plugin": "link:vendor/gally/sylius-plugin",
            "@elastic-suite/gally-sdk": "2.2.2-alpha.0"
          }
        }
        ```
        > **Why `link:` instead of `file:` ?**  
        > Yarn v1 has a known bug with `file:` local packages that declare their own npm dependencies
        > (such as `@elastic-suite/gally-sdk`): it tries to scan a `node_modules` folder inside its cache
        > copy of the package, which does not exist, causing an `EACCES`/`ENOENT` error.  
        > Using `link:` creates a simple symlink to `vendor/gally/sylius-plugin` without going through
        > the cache. `@elastic-suite/gally-sdk` must then be declared explicitly at the root level so
        > that webpack can resolve it.
        >
        > **Development only:** if you are working on the plugin sources directly (i.e. pointing to
        > `link:packages/GallyPlugin` instead of `link:vendor/gally/sylius-plugin`), you must also add
        > a `postinstall` script to build the `@elastic-suite/gally-sdk` dist files, which are not pre-compiled on the GitHub branch:
        > ```json
        > {
        >   "scripts": {
        >     "postinstall": "cd node_modules/@elastic-suite/gally-sdk && yarn install"
        >   }
        > }
        > ```
      - Add the Gally shop entry point and the SDK IIFE copy in your app's `webpack.config.js`:
        ```js
        Encore
            // ... your existing shop config
            .addEntry('gally-shop-entry', './vendor/gally/sylius-plugin/src/Resources/assets/shop/entrypoint.js')
            .copyFiles({
                from: './node_modules/@elastic-suite/gally-sdk/dist/browser/iife',
                to: 'gally/[name].[ext]',
                pattern: /gally-sdk\.global\.js/,
            })
        ;
        ```
        > The `copyFiles()` call exposes `gally-sdk.global.js` as a standalone IIFE script
        > (available as `window.GallySDK`) for use in Twig templates via `{{ asset('build/app/shop/gally/gally-sdk.global.js') }}`.
      - Install JS dependencies and build assets:
        - **Without Docker** (from the Sylius root directory):
          ```shell
          yarn install
          yarn build
          ```
        - **With Docker** (via the `nodejs` container):
          ```shell
          docker compose run --rm nodejs
          ```
          > The `nodejs` container automatically runs `yarn install && yarn build` and mounts the project into `/srv/sylius`.
    - Run `php bin/console doctrine:migrations:migrate` to update the database schema
    - Open Sylius Admin, head to Configuration > Gally and configure the Gally endpoint (URL, credentials), after that enable Gally on your channel (Configuration > Channel > Edit)
- Run this commands from your Sylius instance. This commands must be runned only once to synchronize the structure.
    ```shell
        bin/console gally:structure:sync   # Sync catalog et source field data with gally
    ```
- Run a full index from Sylius to Gally. This command can be run only once. Afterwards, the modified products are automatically synchronized.
    ```shell
        bin/console gally:index            # Index category and product entity to gally
    ```
- At this step, you should be able to see your product and source field in the Gally backend.
- They should also appear in your Sylius frontend when searching or browsing categories.
- And you're done !
- You can also run the command to clean data that are not present in sylius anymore:
    ```shell
        bin/console gally:structure:clean 
    ```

## noUiSlider 

This bundle includes the [noUiSlider](https://github.com/leongersen/noUiSlider) distribution files. 
noUiSlider is "a lightweight, ARIA-accessible JavaScript range slider with multi-touch and keyboard support" which is used in this project for the price slider implementation.

